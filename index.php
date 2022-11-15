<?php
    // Some includes will be presented here (PHP Classes that will be used)

    $isLoggedIn = false;
    $userData = $Accounts->get_user_data();
    
    if ($userData['isLoggedIn']){
        $isLoggedIn = true;
    }

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/styles/main.css">
    <title>Document</title>
</head>
<body dir="rtl">
    <?php
        if ($isLoggedIn){
            include './components/user-header.php';
        }else{
            include './components/visitor-header.php';
        }
    ?>

    <section class="services">
        <div class="perService">....</div>
        <div class="perService">....</div>
        <div class="perService">....</div>
        <div class="perService">....</div>
    </section>

    <footer>...</footer>
</body>
</html>