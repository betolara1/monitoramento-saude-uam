<?php
// Arquivo: processa_edicao.php
include "conexao.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $tipo_doenca = $_POST['tipo_doenca'];
    $historico_familiar = $_POST['historico_familiar'];
    $estado_civil = $_POST['estado_civil'];
    $profissao = $_POST['profissao'];

    // Atualizar os dados na tabela pacientes
    $stmt = $conn->prepare("
        UPDATE pacientes 
        SET tipo_doenca = ?, 
            historico_familiar = ?, 
            estado_civil = ?, 
            profissao = ? 
        WHERE usuario_id = ?
    ");
    
    $stmt->bind_param("ssssi", 
        $tipo_doenca, 
        $historico_familiar, 
        $estado_civil, 
        $profissao, 
        $usuario_id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Dados atualizados com sucesso!'); window.location.href='listar_pacientes.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar os dados!'); window.location.href='listar_pacientes.php';</script>";
    }

    $stmt->close();
} else {
    header('Location: listar_pacientes.php');
    exit;
}
?>