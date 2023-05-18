<?php
require_once "config.php";
$sql = "SELECT * FROM user_logs";
$result = mysqli_query($link, $sql);
if (mysqli_num_rows($result) > 0) {
    ?>
    <html>
    <head>
        <title>Registros de Usuarios</title>
        <style>
        table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f2f2f2;
}

tr:hover {
    background-color: #f5f5f5;
}

td:nth-child(odd) {
    background-color: #eaf7ff; 
}

td:nth-child(even) {
    background-color: #f8e1ff; 
}

th {
    background-color: #dcdcdc; 
}
    </style>
    </head>
    <body>
    <h1>Registros de Usuarios</h1>
    <button onclick="window.location.href='login.php'">Volver al Login</button>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Fecha y Hora</th>
            <th>Actividad</th>
            <th>Dirección IP</th>
            <th>Sistema Operativo</th>
            <th>Navegador</th>
        </tr>
        </thead>
        <tbody>
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['timestamp']}</td>";
            echo "<td>{$row['activity']}</td>";
            echo "<td>{$row['ip_address']}</td>";
            echo "<td>{$row['operating_system']}</td>";
            echo "<td>{$row['browser']}</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
    </body>
    </html>
    <?php
} else {
    echo "No hay registros de usuarios.";
}
mysqli_close($link);
?>
