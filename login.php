<?php
session_start();
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: welcome.php");
    exit;
}
require_once "config.php";
$username = $password = "";
$username_err = $password_err = "";
$login_count = 0; 
function logActivity($message, $ip, $os, $browser, $loginCount){
    $file = fopen("log.txt", "a");
    date_default_timezone_set('America/Mexico_City');
    $timestamp = date("Y-m-d H:i:s");
    $os = PHP_OS;
    $logMessage = $timestamp . " - " . $message . " - Direccion IP: " . $ip .  " - Sistema Operativo: " . $os . " - Navegador: " . $browser . " - Contador de Inicios de Sesion: " . $loginCount . PHP_EOL;
    fwrite($file, $logMessage);
    fclose($file);
}

function getClientIP(){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Por favor ingrese su usuario.";
    } else{
        $username = trim($_POST["username"]);
    }
    if(empty(trim($_POST["password"]))){
        $password_err = "Por favor ingrese su contraseña.";
    } else{
        $password = trim($_POST["password"]);
    }
    if(empty($_POST['g-recaptcha-response'])){
        $captcha_err = "Por favor complete el captcha.";
    } else{
        $secretKey = "6Lc9ftclAAAAAA1eQT_PYGWY6u7-N69eUsOrdd54";
        $captcha = $_POST['g-recaptcha-response'];
        $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha";
        $response = file_get_contents($url);
        $responseKeys = json_decode($response, true);
        if($responseKeys["success"] !== true){
            $captcha_err = "Error al completar el captcha.";
        }
    }
    if(empty($username_err) && empty($password_err) && empty($captcha_err)){
        $sql = "SELECT id, username, password, login_count FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $login_count);
                    if(mysqli_stmt_fetch($stmt)) {
                        $login_count = $login_count ?? 0; 
                        if (password_verify($password, $hashed_password)) {
                            $login_count = $login_count + 3;
                            $update_sql = "UPDATE users SET login_count = ? WHERE id = ?";
                            if($update_stmt = mysqli_prepare($link, $update_sql)){
                                mysqli_stmt_bind_param($update_stmt, "ii", $login_count, $id);
                                mysqli_stmt_execute($update_stmt);
                                mysqli_stmt_close($update_stmt);
                                if(session_status() === PHP_SESSION_NONE) {
                                    session_start();
                                }
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $username;
                                $client_ip = getClientIP();
                                $os = PHP_OS;
                                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                                $browser = "Desconocido";

                                if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) {
                                    $browser = 'Opera';
                                } elseif (strpos($user_agent, 'Edge')) {
                                    $browser = 'Microsoft Edge';
                                } elseif (strpos($user_agent, 'Chrome')) {
                                    $browser = 'Google Chrome';
                                } elseif (strpos($user_agent, 'Safari')) {
                                    $browser = 'Safari';
                                } elseif (strpos($user_agent, 'Firefox')) {
                                    $browser = 'Mozilla Firefox';
                                } elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
                                    $browser = 'Internet Explorer';
                                }

                                logActivity("El usuario $username ha iniciado sesión desde el navegador $browser.", $client_ip, $os, $browser, $login_count);
                                $log_activity = "Registro de usuario: " . $username;
                                $user_id = $_SESSION["id"];
                                $client_ip = getClientIP();
                                $os = PHP_OS;
                                $timestamp = date("Y-m-d H:i:s");
                                $insert_sql = "INSERT INTO user_logs (user_id, timestamp, activity, ip_address, operating_system, browser) VALUES (?, ?, ?, ?, ?, ?)";
                                if ($insert_stmt = mysqli_prepare($link, $insert_sql)) {
                                    mysqli_stmt_bind_param($insert_stmt, "isssss", $user_id, $timestamp, $log_activity, $client_ip, $os, $browser);
                                    mysqli_stmt_execute($insert_stmt);
                                    mysqli_stmt_close($insert_stmt);
                                }

                                header("location: welcome.php");
                                exit;
                            } else {
                                $password_err = "La contraseña que has ingresado no es válida.";
                                $client_ip = getClientIP();
                                logActivity("Error de inicio de sesión para el usuario $username", $client_ip, $os, $browser, $login_count);
                            }
                        }
                    }
                } else{
                    $username_err = "No existe cuenta registrada con ese nombre de usuario.";
                }
            } else{
                echo "Algo salió mal, por favor vuelve a intentarlo.";
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($link);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <link href="style.css" rel="stylesheet" type="text/css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        function disableForm() {
            document.getElementById("username").disabled = true;
            document.getElementById("password").disabled = true;
            document.getElementById("submit").disabled = true;
        }
    </script>
</head>
<body>
<div class="wrapper">
    <h2>Inicio de Sesión</h2>
    <p>Por favor, complete sus credenciales para iniciar sesión.</p>
    <?php if (!empty($username_err) && $login_count >= 3) : ?>
        <div class="alert alert-warning">
            <?php echo $username_err; ?>
        </div>
    <?php else : ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Usuario</label>
                <input type="text" name="username" id="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Contraseña</label>
                <input type="password" name="password" id="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
                <div class="g-recaptcha" data-sitekey="6Lc9ftclAAAAAIzbZTUP2hiJuuBt4WEeasQ3FWPd"></div>
            </div>
            <div class="form-group">
                <?php if ($login_count >= 3) : ?>
                    <script>disableForm();</script>
                    <span>Los campos están bloqueados temporalmente. Por favor, espere 5 minutos.</span>
                <?php else : ?>
                    <input type="submit" id="submit" class="btn btn-primary" value="Ingresar">
                    <a href="table_register.php" class="btn btn-secondary">Tabla de Registro</a>
                <?php endif; ?>
            </div>
            <p>¿No tienes una cuenta? <a href="register.php">Regístrate ahora</a>.</p>
        </form>
    <?php endif; ?>
</div>
</body>
</html>

