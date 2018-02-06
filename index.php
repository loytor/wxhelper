<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <title>Search</title>

    <style type="text/css">
        table tr:nth-child(odd) {
            background-color: lightgreen;
        }

        table td:nth-child(even) {
            background-color: blue;
        }

        table tr:hover {
            background-color: gray !important;
        }

        table td, table th {
            border: 1px solid #EEE;
        }
    </style>
</head>

<body>

<form action="1.php" method="POST">
    username：<input type="text" name="username"/>
    postname:<input type="text" name="postname"/>
    postdate:<input type="text" name="postdate"/>
    <br/>
    <input type="submit"/>
</form>

<div class="content">
    <?php

    define('DBHOST', 'localhost:8889');
    define('DBNAME', 'test');
    define('DBUSER', 'bj');
    define('DBPASS', '1a2b3c');

    //connect db
    function connect_to_db()
    {
        try {
            $connString = "mysql:host=" . DBHOST . ";dbname=" . DBNAME;
            $user = DBUSER;
            $password = DBPASS;
            $pdo = new PDO($connString, $user, $password);
            return $pdo;
        } catch (PDOException $e) {
            echo($e->getMessage());
            exit('Connected failed');
        }
    }

    #1.创建 pdo
    $pdo = connect_to_db();

    #2.组织 查询 参数, (如果为空,不参与查询)
    $username = isset($_POST['username']) && $_POST['username'] ? $_POST['username'] : '';
    $postname = isset($_POST['postname']) && $_POST['postname'] ? $_POST['postname'] : '';
    $postdate = isset($_POST['postdate']) && $_POST['postdate'] ? $_POST['postdate'] : '';

    #3. 根据查询条件组织sql 语句
    $sql = "select username from bloggers";
    $where = '';
    if ($username != '') {
        $where = " where username = '" . $username . "'";
    }
    if ($postname != '') {
        $where = " where postname = '" . $postname . "'";
    }
    if ($postdate != '') {
        $postdate = date('Y-m-d H:i:s', strtotime($postdate));#时间格式转换成,数据库格式
        $where = " where postname <= '" . $postdate . "'";
    }
    $sql .= $where;

    $res = $pdo->query($sql);//准备查询语句
    $html = "<table>";
    foreach ($res as $result){
        $html .= "<tr class='line'>";
        $html .=    "<td>" . $result['id'] . "</td>";
        $html .=    "<td>" . $result['username'] . "</td>";
        $html .=    "<td>" . $result['password'] . "</td>";
        $html .=    "<td>" . $result['blogname'] . "</td>";
        $html .=    "<td>" . $result['postname'] . "</td>";
        $html .=    "<td>" . $result['postdate'] . "</td>";
        $html .= "</tr>";
    }
    $html .= "</table>";

    echo $html;
    ?>
</div>

</body>
</html>
