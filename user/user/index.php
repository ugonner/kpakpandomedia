<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/api/user/user.class.php';
include_once $_SERVER['DOCUMENT_ROOT']. '/api/includes/helpers/encodepassword.php';
include_once $_SERVER['DOCUMENT_ROOT']. '/api/includes/helpers/mediafilehandler.php';

//update last activity;
if(isset($_GET["updateacitivity"])){
    session_start();
    if(isset($_SESSION["userid"])){
        $id = $_SESSION["userid"];
        $email = $_SESSION["email"];
        $property = "lastactivity";
        $value = date("Ymdhis");
        $user = new user();
        $user->editUser($id,$email,$property,$value);
    }

}
//get wards;
if(isset($_GET["getroles"])){
    $user = new user();
    $roles = $user ->getRoles();
    $options = "<option value=''>Please Select</option> ";
    for($i = 0; $i < count($roles); $i++){
        $option = " "."<option value='".$roles[$i][0]."'>".$roles[$i][1]."</option>";
        $options .= $option;
    }
    echo($options);
    exit();
}


//get user;

if(isset($_GET['guid'])){
    $uid = $_GET['guid'];
    $user = new User();
    if($user = $user -> getUser($uid)){
        include $_SERVER['DOCUMENT_ROOT'].'/api/user/userprofile.html.php';
        exit();
    }
}


if(isset($_GET['gue'])){

    $email = $_GET['email'];
    $user = new User();
    if($user->isLoggedIn()){
        if($user = $user -> getUserByEmail($email)){
            include_once $_SERVER['DOCUMENT_ROOT'].'/api/user/userprofile.html.php';
            exit;
        }
    }
}

//get users in a in an sublocation;
if((isset($_GET["gulbr"])) && (isset($_GET["lid"]))){
    $lid = $_GET["lid"];
    $rid = $_GET["rid"];
    $user = new user();
    $users = $user->getUsersInsublocationByRole($lid,$rid);
    if($users!= null){
         $title = $users[0]["rolename"]."s In " .$users[0]["sublocationname"]." Of Anambra location, Nigeris";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/user/users.html.php";
         exit();
    }else{
        $error = "No Users In This Search Category Yet Check Back Soon";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/includes/errors/error.html.php";
        exit();
    }
}


if(isset($_GET['gubr'])){
    $rid = htmlspecialchars($_GET['rid']);

    if(isset($_GET["pgn"])){
        $pgn = $_GET["pgn"];
    }else{
        $pgn = 0;
    }

    $amtperpage = 10;
    $sql = 'SELECT count(id) FROM user WHERE roleid = :roleid';

    $db = new Dbconn();
    $dbh = $db->dbcon;
    try{
        $stmt = $dbh -> prepare($sql);
        $stmt->bindParam(":roleid",$rid);
        $stmt -> execute();
        $counter = $stmt -> fetch();

    }
    catch(PDOException $e){
        $error = "Unable TO Count the articles";
        $error2 = $e -> getMessage();
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/includes/errors/error.html.php";
        exit();
    }

    $no_of_pages = ceil($counter[0] / $amtperpage);


    $user = new User();
    if($users = $user -> getUsersByRole($rid,$pgn,$amtperpage)){
        $title = "Welcome to ".$users[0]["rolename"]."s' Room";
    }
    include_once $_SERVER['DOCUMENT_ROOT'].'/api/user/users.html.php';
    exit;
}


if(isset($_GET['gubp'])){
    $value = htmlspecialchars($_GET['value']);
    $property = htmlspecialchars($_GET['property']);
    $property_alias = htmlspecialchars($_GET['property-alias']);
    if(($property == 'dateofbirth') || ($property == 'all') || $property == 'all'){
        $property = 'wq';
        $value = (($property == 'dateofbirth')? 'RIGHT("dateofbirth",5) = '.date("m-d") : 'user.id > 0');
    }

    $pgn = (empty($_GET["pgn"])? 0: htmlspecialchars($_GET["pgn"]));
    $amtperpage = 10;



   $presql = 'SELECT count(*) FROM user INNER JOIN role ON user.roleid = role.id
	            INNER JOIN location ON location.id = locationid
	            INNER JOIN sublocation ON sublocation.id = sublocationid
	            WHERE ';

    if(($property == 'wq') ){
        $sql = $presql.$value;
    }else{
        $sql = $presql.$property.' = :value';
    }

    $db = new Dbconn();
    $dbh = $db->dbcon;
    try{
        $stmt = $dbh -> prepare($sql);
        $stmt->bindParam(":value",$value);
        $stmt -> execute();
        $counter = $stmt -> fetch();

    }
    catch(PDOException $e){
        $error = "Unable TO Count the articles";
        $error2 = $e -> getMessage();
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/includes/errors/error.html.php";
        exit();
    }

    $no_of_pages = ceil($counter[0] / $amtperpage);

    $user = new User();
    if($users = $user -> getUsersByProperty($property, $value,$pgn,$amtperpage)){
        $title = "Welcome to "." Room";
    }
    include_once $_SERVER['DOCUMENT_ROOT'].'/api/user/users.html.php';
    exit;
}

//get users in an location by role;
if((isset($_GET["gusbr"])) && (isset($_GET["sid"]))){
    $sid = $_GET["sid"];
    $rid = $_GET["rid"];
    $user = new user();
    if($users = $user->getUsersInlocationByRole($sid,$rid)){
        $title = $users[0]["rolename"]."s In ".$users[0]["locationname"]." Local Government Area, Anambra location, Nigeria";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/user/users.html.php";
        exit();
    }else{
        $error = 'No Persons Yet Available For This location Check Back Soonest';
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/includes/errors/error.html.php";
        exit();
    }
}


//edituser;
if(isset($_POST["edituser"])){
    session_start();
    if(isset($_SESSION["userid"])){
        $uid = $_SESSION["userid"];
        $pty = htmlspecialchars($_POST["pty"]);
        $value = ((empty($_POST['value']))? '01' : htmlspecialchars($_POST['value']));
    }else{
        $error = "You Are Not Identified With This Profile, lOGIN As Owner";
        header("Location: /api/ward/index.php?wid=1");
        exit();
    }
    session_write_close();
    $file="profilepic";
    $folder = "/api/img/users/";
    if($profilepic=storeFile($file,$folder,'user image')){
        $pty = "profilepic";
        $value = $profilepic["displayname"];
    }

    //for dateofbirth
    if($_POST['pty']== 'dateofbirth'){
        $pty = 'dateofbirth';
        $day = ((empty($_POST['day']))? '01' : htmlspecialchars($_POST['day']));
        $month = ((empty($_POST['month']))? '01' : htmlspecialchars($_POST['month']));
        if((strlen($_POST['year'])==4)){
            $year =  $_POST['year'];
        }else{
            $year = '2000';
        }
        $value = $year.'-'.$month.'-'.$day;

    }
    $user = new user();
    if($user->editUser($uid,$pty,$value)){
        $output = "Profile Information Successfully Edited";
        header("Location:/api/user/index.php?uid=".$uid."&output=".$output);
        exit();
    }
}

//registration data validation;
if(isset($_POST['register'])){

    session_start();
    if(($_POST['password'])!= ($_POST['password2'])){
        $error='Sorry Passwords Didn\'t match:
        Re-enter your passwords';
        include_once $_SERVER['DOCUMENT_ROOT'].'/api/user/registration.html.php';
        exit();
    }


    //validate email;
    if(!preg_match('/[-!\.,@#\$%\^\&\*\()_\+=\/a-z0-9]+@[-a-z0-9\_\.,]+\.[-a-z0-9\_\.,]+/i'
        , $_POST["email"])){
        $error = 'This Email Is Not Set, Please Put A valid Email Address';
        include $_SERVER['DOCUMENT_ROOT'].'/api/user/registration.html.php';
        exit();
    }else{
        $email = htmlspecialchars($_POST['email']);
    }

    $DOB= null;

    //validate mobile number;
 if(isset($_POST["mobile"]) && (trim($_POST["mobile"])!= "")){
    if(isset($_POST["foreigner"])){
        if(empty($_POST["zip"])){
           $error = 'Zip code is empty, if you reside outside Nigeria Please enter a zipxode
           for your country of residence';
            include $_SERVER['DOCUMENT_ROOT'].'/api/user/registration.html.php';
            exit();
        }else{
            $mobile = htmlspecialchars($_POST["zip"].substr($_POST["mobile"],1));
        }
    }else{
        $mobile = htmlspecialchars("234".substr($_POST["mobile"],1));
    }
 }else{
      $error = 'Mobile Number Is Not Set';
     include_once $_SERVER['DOCUMENT_ROOT'].'/api/user/registration.html.php';
     exit();
 }
//validate public for mobile;
    if(isset($_POST["public"])){
        $public = "N";
    }else{
        $public = "Y";
    }

    $password = encodePassword($_POST["password"]);
    $firstname = htmlspecialchars($_POST['firstname']);
    $surname = ((empty($_POST['surname']))? NULL : htmlspecialchars($_POST['surname']));
    $dateofbirth = htmlspecialchars($DOB);
    $gender = NULL;
    $about = "Just Me";
    $school = "Not Available";
    $dateofregistration = date('YmdHis');

    $locationid = ((empty($_POST['locationid']))? 38 : htmlspecialchars($_POST['locationid']));
    $sublocationid = ((empty($_POST['sublocationid']))? 768 : htmlspecialchars($_POST['sublocationid']));
    $roleid = 2;
    $rolenote = "A big fan";
    $displayname = null;

//call to user class;
$register = new user();
    if($register ->registerUser($firstname,$surname,$email,$password,$mobile,$gender,
    $dateofbirth,$dateofregistration,$about,$locationid,$sublocationid,$school,$displayname,$roleid,$rolenote,$public)){
        $output = "Congrats! ".$firstname." , You Have Successfully Registered";
        session_start();
        $_SESSION["email"] = $email;
        //use post password cos it's the NOT SALTED version used for login
        $_SESSION["password"] = $_POST["password"];
        session_write_close();
    }
    $url="Location: /api/user/index.php?gue&email=".$email."&output=".$output;
    header($url);
    exit();
}
//above is the end of the rtgister;

//get user articles;
if(isset($_GET["getuserarticles"])){
    $uid = $_GET["uid"];
    if(isset($_GET["pgn"])){
        $pgn = $_GET["pgn"];
    }else{
        $pgn = 0;
    }

    $amtperpage = 10;
    $sql = 'SELECT count(id) FROM article WHERE userid = :userid';

    $db = new Dbconn();
    $dbh = $db->dbcon;
    try{
        $stmt = $dbh -> prepare($sql);
        $stmt->bindParam(":userid",$uid);
        $stmt -> execute();
        $counter = $stmt -> fetch();

    }
    catch(PDOException $e){
        $error = "Unable TO Count the articles";
        $error2 = $e -> getMessage();
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/includes/errors/error.html.php";
        exit();
    }

    $no_of_pages = ceil($counter[0] / $amtperpage);

    $user = new user();
    if($UAs = $user->getUserArticles($uid,$amtperpage,$pgn)){
        $user = $UAs["user"];
        $userarticles = $UAs["userarticles"];
        include_once $_SERVER['DOCUMENT_ROOT']."/api/user/userprofile.html.php";
        exit();
        }else{
        $error = "This Person Probably Has No Article To His Name OR It  Has Been deleted";
        include_once $_SERVER['DOCUMENT_ROOT']."/api/includes/errors/error.html.php";
        exit();
    }
}
//RESET PASSWORD;
if(isset($_POST["resetpassword"])){
    session_start();
    if(!($_POST["password"] == $_SESSION["password2"])){
        $error = "Your Old Password Did Not Match The Account's Password, If You
        Are The Owner Of This Account Please Contact the <a href='/api/contact.html.php'>
        <b>Support Team </b> </a>";
        include $_SERVER["DOCUMENT_ROOT"].'/api/user/forms/resetpassword.html.php';
        exit();
    }
    if(($_POST['newpassword1'])!== ($_POST['newpassword2'])){
        $error='You Might have Misspelt Your New Password. The Two New Passwords Sid Not Match:
        Re-enter your passwords';
        include $_SERVER['DOCUMENT_ROOT'].'/api/user/forms/resetpassword.html.php';
        exit();
    }

    $uid = $_SESSION["userid"];
    $email = $_SESSION["email"];
    $oldpassword = $_POST["oldpassword"];
    $newpassword = $_POST["password"];
$user = new user();
    if($user->resetPassword($uid,$email,$oldpassword,$newpassword)){
        $url = "Location:'/api/user/index.php?uid='".$uid;
        header($url);
    }else{
        $error = "An Error Occurred Resetting Password, Password Not Reset";
        include $_SERVER["DOCUMENT_ROOT"].'/api/forms/resetpassword.html.php';
        exit();
    }
}
//PUT AT LAST TO RECOVER PASSWORDS;


//getusertransactions;
//get useractedarticles;
if(isset($_GET["gmt"])){
    $transactionid = $_GET["trxid"];
    session_start();
    $userid = $_SESSION["userid"];
    $amtperpage = 3;
    if(isset($_GET["pgn"])){
        $pgn = $_GET["pgn"];
    }else{
        $pgn = 0;
    }

    $sql = 'SELECT count(id) FROM usertransaction'.$userid.' WHERE transactionid = :transactionid';

    $db = new Dbconn();
    $dbh = $db->dbcon;
    try{
        $stmt = $dbh -> prepare($sql);
        $stmt -> bindParam(":transactionid",$transactionid);
        $stmt -> execute();
        $counter = $stmt -> fetch();

    }
    catch(PDOException $e){
        $error = "Unable TO Count article";
        $error2 = $e -> getMessage();
        include_once $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
        exit();
    }
    $no_of_pages = $counter[0] / $amtperpage;

    $user = new user();
    if($mytransactions = $user->getUserTransactions($userid,$transactionid,$amtperpage,$pgn)){
        $title = "Transaction:  Your ".$mytransactions[0]["transactionname"]." Transactions";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/user/mytransactions.html.php";
        exit();
    }else{
        $output = "Sorry, You Do Not Have Such Transaction Or The Transactions Have Been Proscribed";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/user/mytransactions.html.php";
        exit();
    }
}
//GET ACTIVE USERS;
//get active persons;
if(isset($_GET["getactiveusers"])){
    if(isset($_GET["pgn"])){
        $pgn = $_GET["pgn"];
    }else{
        $pgn = 0;
    }

    $interval = 420;
    $time = time();
    $amtperpage = 10;
    $sql = 'SELECT count(id) FROM user WHERE ('.$time.' - lastactivity ) <= '.$interval;

    $db = new Dbconn();
    $dbh = $db->dbcon;
    try{
        $stmt = $dbh -> prepare($sql);
        $stmt -> execute();
        $counter = $stmt -> fetch();

    }
    catch(PDOException $e){
        $error = "Unable TO Count article";
        $error2 = $e -> getMessage();
        echo $error2."  users not counted";
        exit();
    }

    $no_of_pages = $counter[0] / $amtperpage;
    $user = new user();
    if($activeusers = $user->getActiveUsers($time,$interval,$amtperpage,$pgn)){
        $html = "<h5>Chat</h5>";
        for($i=0; $i < count($activeusers); $i++){
            $text = "<div style='background-color: brown; margin: 5px; border-radius: 5%;'>
                <a href='/api/user/?guid=".$activeusers[$i][0]."' style='color: #ffffff;'>
                <span class='glyphicon glyphicon-user'></span>
                ".$activeusers[$i]["firstname"]."
                </a>
            </div>";
           $html = $html . $text;
        }
        $html .= "<div><a href='/api/user/?seeallliveusers'>
           Chat Room <span class='glyphicon glyphicon-forward'></span></a></div>";
        echo($html);
        exit;
    }else{
        echo "No One Is Currently Online, Check Again Soonest";
        exit;
    }
}

//for pages;


    //GET ACTIVE USERS;
//get active persons;
if(isset($_GET["seeallliveusers"])){
        if(isset($_GET["pgn"])){
            $pgn = $_GET["pgn"];
        }else{
            $pgn = 0;
        }

        $interval = 420;
        $time = time();
        $amtperpage = 10;
        $sql = 'SELECT count(id) FROM user WHERE ('.$time.' - lastactivity ) <= '.$interval;

        $db = new Dbconn();
        $dbh = $db->dbcon;
        try{
            $stmt = $dbh -> prepare($sql);
            $stmt -> execute();
            $counter = $stmt -> fetch();

        }
        catch(PDOException $e){
            $error = "Unable TO Count article";
            $error2 = $e -> getMessage();
            echo "users not counted";
            exit();
        }

        $no_of_pages = ceil($counter[0] / $amtperpage);
        $user = new user();
        if($users = $user->getActiveUsers($time,$interval,$amtperpage,$pgn)){
            $title = "Live Users";
            include_once $_SERVER["DOCUMENT_ROOT"]."/api/user/users.html.php";
            exit();
        }else{
            $error = "No Bosy Online Yet";
            include_once $_SERVER["DOCUMENT_ROOT"]."/api/includes/errors/error.html.php";
            exit();
        }

    }




$user = new user();
//password recovery;
$user->recoverPassword();
//log in call;
if(!$user->isLoggedIn()){
    $error="Please Login First With Correct Email And Password Pair";
    include_once $_SERVER["DOCUMENT_ROOT"]."/api/user/forms/loginform.html.php";
    exit();
}
$uid=$_SESSION["userid"];
header("Location:/api/user/index.php?guid=".$uid);
exit();
?>