<?php

$cells = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
$player = 'x';
$moveCount = 0;
$isGameOver = false;
$winner = null;

session_start();

if (isset($_SESSION['cells'])) {
    $cells = $_SESSION['cells'];
}

if (isset($_SESSION['player'])) {
    $player = $_SESSION['player'];
}

if (isset($_SESSION['moveCount'])) {
    $moveCount = $_SESSION['moveCount'];
}

if (isset($_SESSION['isGameOver'])) {
    $isGameOver = $_SESSION['isGameOver'];
}

if (isset($_SESSION['winner'])) {
    $winner = $_SESSION['winner'];
}

function parse_cell($cell) {
    if (strlen($cell) != 3) {
        return false;
    }

    $cell = explode(':', $cell);

    if (count($cell) != 2) {
        return false;
    }

    $r = (int)$cell[0];
    $c = (int)$cell[1];

    return [$r, $c];
}

function new_game() {
    global $cells;
    global $player;
    global $moveCount;
    global $isGameOver;
    global $winner;
    $cells = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
    $player = 'x';
    $moveCount = 0;
    $isGameOver = false;
    $winner = null;
}

function check_winner($r, $c) {
    global $cells;

    // check row
    $count = 0;
    $winner_cells = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
    for ($i = 0; $i < 3; $i++) {
        if ($cells[$r][$i] === $cells[$r][$c]) {
            $winner_cells[$r][$i] = 1;
            $count += 1;
        }

        if ($count === 3) {
            return [$cells[$r][$c], $winner_cells];
        }
    }

    // check col
    $count = 0;
    $winner_cells = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
    for ($i = 0; $i < 3; $i++) {
        if ($cells[$i][$c] === $cells[$r][$c]) {
            $winner_cells[$i][$c] = 1;
            $count += 1;
        }

        if ($count === 3) {
            return [$cells[$r][$c], $winner_cells];
        }
    }

    // check diagonal
    $count = 0;
    $winner_cells = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
    for ($i = 0; $i < 3; $i++) {
        if ($cells[$i][$i] === $cells[$r][$c]) {
            $winner_cells[$i][$i] = 1;
            $count += 1;
        }

        if ($count === 3) {
            return [$cells[$r][$c], $winner_cells];
        }
    }

    // check inverse diagonal
    $count = 0;
    $winner_cells = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];
    for ($i = 0; $i < 3; $i++) {
        if ($cells[$i][2 - $i] === $cells[$r][$c]) {
            $winner_cells[$r][2 - $i] = 1;
            $count += 1;
        }

        if ($count === 3) {
            return [$cells[$r][$c], $winner_cells];
        }
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'newgame') {
        new_game();
    } else if (isset($_POST['action']) && $_POST['action'] === 'turn' && !$isGameOver) {
        $cell = parse_cell($_POST['cell']);

        if ($cell === false) {
            header('Status Code: 400');
            echo 'Invalid cell';
            exit();
        }

        [$r, $c] = $cell;

        if ($cells[$r][$c] === 0) {
            if ($player === 'x') {
                $cells[$r][$c] = 'x';
                $player = 'o';
            } else {
                $cells[$r][$c] = 'o';
                $player = 'x';
            }

            $moveCount += 1;

            if ($moveCount >= 5) {
                $winner = check_winner($r, $c);

                if ($winner !== null || $moveCount === 9) {
                    $isGameOver = true;
                }
            }
        }
    }
}

$_SESSION['cells'] = $cells;
$_SESSION['player'] = $player;
$_SESSION['moveCount'] = $moveCount;
$_SESSION['isGameOver'] = $isGameOver;
$_SESSION['winner'] = $winner;

session_commit();

$cells_html = '';
for ($r = 0; $r < 3; $r++) {
    $cells_html .= '<tr>';

    for ($c = 0; $c < 3; $c++) {
        $css = ['cell'];

        if ($winner && $winner[1][$r][$c] === 1) {
            $css[] = 'winner';
        }

        if ($cells[$r][$c] == 'x') {
            $css[] = 'x';
        } else if ($cells[$r][$c] == 'o') {
            $css[] = 'o';
        } else {
            $css[] = 'empty';
        }

        $cells_html .= '<td class="' . implode(' ', $css) . '"><form action="server.php" method="POST"><input type="hidden" name="action" value="turn"><input type="hidden" name="cell" value="'.$r.':'.$c.'"><button></button></form></td>';
    }

    $cells_html .= '</tr>';
}

function callback($buffer){
    global $cells_html;
    global $player;
    global $moveCount;
    global $isGameOver;
    global $winner;

    $message = 'Moves left ' . (9 - $moveCount) . '. Turn (' . $player . ')';

    $buffer = str_replace('${CELLS}', $cells_html, $buffer);

    if ($isGameOver) {
        $message = 'Game over.';

        if ($winner !== null) {
            $message .= ' Winner (' . $winner[0] . ')';
        }
    }

    $buffer = str_replace('${MESSAGE}', $message, $buffer);

    header('Content-Length: ' . strlen($buffer));

    return $buffer;
}

header('Content-Type: text/html');

ob_start('callback');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tic Tac Toe</title>
    <script defer src="https://unpkg.com/htmx.org@2.0.2" integrity="sha384-Y7hw+L/jvKeWIRRkqWYfPcvVxHzVzn5REgzbawhxAuQGwX1XWe70vji+VSeHOThJ" crossorigin="anonymous"></script>
    <style>
        html, body {
            min-height: 100%;
            margin: 0;
        }
        .grid {
            table-layout: fixed;
            border-spacing: 2px;
        }
        .cell {
            width: 100px;
            height: 100px;
            border: 1px solid black;

            &.winner {
                background-color: green;
                color: white;
            }

            & form {
                width: 100%;
                height: 100%;
                & button {
                    width: 100%;
                    height: 100%;
                    display: block;
                    border: none;
                    padding: 0;
                    transition: all 0.2s ease-in;
                    background-color: transparent;
                    color: inherit;

                    &:hover {
                        cursor: pointer;
                        background-color: yellow;
                    }
                }
            }

            &.x,
            &.o {
                & form button {
                    pointer-events: none;
                }
            }

            &.o form button::after {
                font-size: 50px;
                content: 'o';
            }

            &.x form button::after {
                font-size: 50px;
                content: 'x';
            }
            &.o form button::after {
                font-size: 50px;
                content: 'o';
            }
        }
    </style>
</head>
<body hx-boost="true">
    <div>${MESSAGE}</div>
    <table class="grid">
        <tbody>
            ${CELLS}
        </tbody>
    </table>
    <form action="server.php" method="POST">
        <input type="hidden" name="action" value="newgame">
        <button>New game</button>
    </form>
</body>
</html>
