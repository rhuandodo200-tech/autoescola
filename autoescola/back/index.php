
<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") exit;

require "config.php";

$rota = $_GET["rota"] ?? "teste";

function responder($dados) {
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function consultar($con, $sql) {
    responder($con->query($sql)->fetchAll(PDO::FETCH_ASSOC));
}

function cadastrar($con, $tabela, $campos, $dados) {
    foreach ($campos as $campo) {
        if (empty($dados[$campo])) {
            http_response_code(400);
            responder(["erro" => "Preencha todos os campos"]);
            return;
        }
    }

    try {
        $sql = "INSERT INTO $tabela (" . implode(",", $campos) .
               ") VALUES (" . implode(",", array_map(fn($c) => ":$c", $campos)) . ")";
        $stmt = $con->prepare($sql);
        $valores = [];

        foreach ($campos as $campo) {
            $valores[":".$campo] = $dados[$campo];
        }

        $stmt->execute($valores);
        responder(["sucesso" => true, "mensagem" => "Cadastro realizado!"]);
    } catch (PDOException $e) {
        http_response_code(400);
        responder(["erro" => "Não foi possível cadastrar. Verifique os dados e o e-mail."]);
    }
}

switch ($rota) {
    case "alunos":
        consultar($con, "SELECT * FROM Alunos");
        break;

    case "instrutores":
        consultar($con, "SELECT * FROM Instrutores");
        break;

    case "aulas":
        consultar($con, "SELECT * FROM Aulas");
        break;

    case "emails":
        consultar($con, "SELECT email FROM Alunos");
        break;

    case "categorias":
        consultar($con, "SELECT categoria_cnh FROM Instrutores");
        break;

    case "total aulas":
        consultar($con, "SELECT COUNT(*) AS total_aulas FROM Aulas");
        break;

    case "aulasalunos":
        consultar($con, "SELECT Aulas.*, Alunos.nome AS aluno FROM Aulas INNER JOIN Alunos ON Aulas.aluno_id = Alunos.id");
        break;

    case "aulasinstrutores":
        consultar($con, "SELECT Aulas.*, Instrutores.nome AS instrutor FROM Aulas INNER JOIN Instrutores ON Aulas.instrutor_id = Instrutores.id");
        break;

    case "aulascompletas":
        consultar($con, "SELECT Aulas.*, Alunos.nome AS aluno, Instrutores.nome AS instrutor FROM Aulas INNER JOIN Alunos ON Aulas.aluno_id = Alunos.id INNER JOIN Instrutores ON Aulas.instrutor_id = Instrutores.id");
        break;

    case "aulastipo":
        consultar($con, "SELECT tipo, COUNT(*) AS total FROM Aulas GROUP BY tipo");
        break;

    case "praticas":
        consultar($con, "SELECT * FROM Aulas WHERE tipo = 'prática'");
        break;

    case "teoricas":
        consultar($con, "SELECT * FROM Aulas WHERE tipo = 'teórica'");
        break;

    case "cadastraraluno":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            responder(["erro" => "Use POST"]);
            break;
        }

        $dados = json_decode(file_get_contents("php://input"), true) ?? [];
        cadastrar($con, "Alunos", ["nome", "email", "telefone", "data_nascimento"], $dados);
        break;

    case "cadastrarinstrutor":
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            http_response_code(405);
            responder(["erro" => "Use POST"]);
            break;
        }

        $dados = json_decode(file_get_contents("php://input"), true) ?? [];
        cadastrar($con, "Instrutores", ["nome", "email", "telefone", "categoria_cnh"], $dados);
        break;

    default:
        responder(["erro" => "Escolha uma rota válida"]);
}
?>

