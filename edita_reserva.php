<?php
include 'inc/query.php'; 

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$id = $_GET['id'];

selectID("reservas", $id, $conn);

if ($resultado && $resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $sala_id = $row['sala_id'];
    $data_inicio = $row['data_inicio'];
    $mesI = substr($data_inicio, 5, 2);
    $diaI = substr($data_inicio, 8, 2);
    $anoI = substr($data_inicio, 0, 4);
    $data_fim = $row['data_fim'];
    $url = $row['url'];
    $hora_inicio = date('H:i', strtotime($data_inicio));
    $hora_fim = date('H:i', strtotime($data_fim));
    $duracao = strtotime($data_fim) - strtotime($data_inicio);
    $duracao_horas = gmdate("H:i:s", $duracao);
} else {
    echo "Nenhum elemento encontrado com o ID fornecido.";
    exit();
}

$reservas = [];
while ($row = $resultado->fetch_assoc()) {
    $reservas[] = $row;
}

select_sala($conn);
$nome_sala = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_inicio = $_POST['data_inicio'];
    $data_inicio = "$anoI-$mesI-$diaI $data_inicio:00";
    $duracao = $_POST['duracao'];
    $data_fim = date("Y-m-d H:i:s", strtotime($data_inicio) + strtotime($duracao) - strtotime("TODAY"));
    $sala_id = $_POST['sala_id'];
    $url = $_POST['url'];

    if (!empty($sala_id) && !empty($data_inicio) && !empty($duracao)) {
        update_R($data_inicio, $data_fim, $url, $id, $sala_id, $user_id, $conn);
        echo "Reserva atualizada com sucesso.";
        header('Location: calendario.php');
        exit();
    } else {
        echo "Erro ao atualizar a reserva.";
        echo "Erro: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Reservas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <main>
        <div class="page">
            <form method="post" id="formLogin" class="formLogin">
            <h1 id="top">Reservar sala</h1>

<label for="sala_id">Salas</label>
<div class="dropdown">
    <input readonly onclick="myFunction('salaDropdown')" placeholder="<?php echo !empty($nome_sala) ? $nome_sala : 'Select Sala'; ?>" class="dropbtn">
    <div id="salaDropdown" class="dropdown-content">
        <input type="text" id="salaInput" onkeyup="filterFunction('salaInput', 'salaDropdown')" onclick="event.stopPropagation()">
        <?php
        while ($sala = $resultado->fetch_assoc()) {
            echo '<a href="#" onclick="selectSala(' . $sala['id'] . ', \'' . $sala['nome_sala'] . '\')">' . $sala['nome_sala'] . '</a>';
        }
        ?>
    </div>
</div>

<input type="hidden" id="sala_id" name="sala_id" value="<?php echo $sala_id; ?>" required>

                <label for="data_inicio">Hora de início de reserva</label>
                <select name="data_inicio" id="data_inicio">
                    <?php
                    function isAvailable($time, $reservas) {
                        foreach ($reservas as $reserva) {
                            $reserva_inicio = date("H:i", strtotime($reserva['data_inicio']));
                            $reserva_fim = date("H:i", strtotime($reserva['data_fim']));
                            if ($time >= $reserva_inicio && $time < $reserva_fim) {
                                return false;
                            }
                        }
                        return true;
                    }

                    $times = [];
                    for ($i = 7; $i < 21; $i++) {
                        $time = sprintf('%02d:00', $i);
                        if (isAvailable($time, $reservas)) {
                            $times[] = $time;
                        }
                    }

                    foreach ($times as $time) {
                        $selected = ($time == $hora_inicio) ? 'selected' : '';
                        echo "<option value=\"$time\" $selected>$time</option>";
                    }
                    ?>
                </select>

                <label for="duracao">Duração da reserva:</label>
                <select name="duracao" id="duracao">
                    <?php
                    for ($i = 1; $i <= 8; $i++) {
                        $duracao_option = sprintf('0%d:00:00', $i);
                        $selected = ($duracao_option == $duracao_horas) ? 'selected' : '';
                        echo "<option value=\"$duracao_option\" $selected>$i hrs</option>";
                    }
                    ?>
                </select>

                <label for="url">Url da reunião online</label>
                <input id="url" type="text" name="url" placeholder="insira aqui o URL da reuniao on-line" value="<?php echo $url; ?>">

                <input id="button" type="submit" value="Salvar" class="btn" id="enviar">
            </form>
        </div>
    </main>

    <script>
        function myFunction(dropdownId) {
            document.getElementById(dropdownId).classList.toggle("show");
        }

        function filterFunction(inputId, dropdownId) {
            var input, filter, div, a, i;
            input = document.getElementById(inputId);
            filter = input.value.toUpperCase();
            div = document.getElementById(dropdownId);
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                txtValue = a[i].textContent || a[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    a[i].style.display = "";
                } else {
                    a[i].style.display = "none";
                }
            }
        }

        function selectSala(id, name) {
            document.getElementById("sala_id").value = id;
            document.querySelector(".dropbtn").innerText = name;
            document.getElementById("salaDropdown").classList.remove("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn') && !event.target.matches('#salaInput')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>
</html>