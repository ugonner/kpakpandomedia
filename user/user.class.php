<?php
include_once $_SERVER['DOCUMENT_ROOT']. '/api/includes/db/connect2.php';
include_once $_SERVER['DOCUMENT_ROOT']. '/api/includes/helpers/encodepassword.php';
include_once $_SERVER['DOCUMENT_ROOT']. '/api/user/usermessage/usermessage.class.php';

class user{
  protected $firstname;
  protected $surname;
  protected $email;
  protected $password;
  protected $mobile;
  protected $gender;
  protected $profilepic;
  protected $locationid;
  protected $about;
  protected $school;
  protected $dateofregistration;
  protected $dateofbirth;

  protected $db;

  public function __construct(){
     $dbh = new Dbconn();
     $this -> db = $dbh->dbcon;
  }

//get active users;
    public function getActiveUsers($time,$interval,$amtperpage,$pgn){
        $sql = '(SELECT user.id,firstname,surname,profilepic,
                role.name as rolename, mobile,gender,
                location.name AS locationname,sublocation.name AS sublocationname
	            FROM user INNER JOIN role ON user.roleid = role.id
	            INNER JOIN location ON location.id = locationid
	            INNER JOIN sublocation ON sublocation.id = sublocationid
	            WHERE ('.$time.' - lastactivity ) <= :interval LIMIT '.$pgn.','.$amtperpage.')';
        try{
            $stmt = $this -> db -> prepare($sql);

            $stmt -> bindParam(":interval", $interval);
            $stmt -> execute();
            $users = $stmt -> fetchAll();
            return $users;
        }
        catch(PDOException $e){
            echo $e -> getMessage();
            return FALSE;
        }
//end of catch;
    }
//end of getactive user;

  public function getUser($id){
    $sql = '(SELECT user.id,firstname,surname,dateofbirth,mobile,
	school,dateofregistration,profilepic,about,rolenote,role.name AS rolename, location.name AS locationname,sublocation.name AS sublocationname,
	public,gender,lastactivity
	FROM user
	INNER JOIN role ON user.roleid = role.id
    INNER JOIN location ON
	user.locationid = location.id
	INNER JOIN sublocation ON user.sublocationid = sublocation.id
    WHERE user.id = :id)';
try{
    $stmt = $this -> db -> prepare($sql);

    $stmt -> bindParam(":id", $id);
    $stmt -> execute();

    $user = $stmt -> fetch();
}
 catch(PDOException $e){
    echo $e -> getMessage();
    return FALSE;
}
//end of catch;
      if(!empty($user)){
          //substitute empty user images;
              if(empty($user['profilepic'])){
                  $user['profilepic'] = "/api/img/users/user.jpg";
              }
          return $user;

      }else{
       return FALSE;
   }
  }
//end of getUSER;
//get users products;


//get user by email

    public function getUserByEmail($email){
        $sql = '(SELECT user.id,firstname,surname,dateofbirth,mobile,
	school,dateofregistration,profilepic,about,rolenote,role.name AS rolename, location.name AS locationname,sublocation.name AS sublocationname,
	 public,gender,lastactivity
	FROM user INNER JOIN role ON user.roleid = role.id INNER JOIN location ON
	user.locationid = location.id INNER JOIN sublocation ON user.sublocationid = sublocation.id
    WHERE user.email = :email)';
        try{
            $stmt = $this -> db -> prepare($sql);

            $stmt -> bindParam(":email", $email);
            $stmt -> execute();

            $user = $stmt -> fetch();
        }
        catch(PDOException $e){
            echo $e -> getMessage();
            return FALSE;
        }

        //substitute empty user images;
        if(!empty($user)){
            if(empty($user['profilepic'])){
                $user['profilepic'] = "/api/img/users/user.jpg";
            }
            return $user;
        }else{
            return FALSE;
        }

    }
//end of getUSER;
//end of getUsersA
  public function getUserArticles($userid,$amtperpage,$pgn){
      if($pgn == 0){
          $limit = $amtperpage;
          $offset = 0;
      }else{
          $limit = $amtperpage;
          $offset = $pgn * $amtperpage;
      }
      $userarticles = array();
      $sql = '(SELECT user.id,firstname,surname,dateofbirth,mobile,
	school,dateofregistration,profilepic,about,rolenote,role.name, location.name,sublocation.name,
	 public,gender
	FROM user INNER JOIN role ON user.roleid = role.id INNER JOIN location ON
	user.locationid = location.id INNER JOIN sublocation ON user.sublocationid = sublocation.id
	WHERE user.id = :userid)
UNION (SELECT  article.id, title,dateofpublication, categoryid,category.name,
	userid,6,7,8,9,10,11,13,14,23
	FROM article INNER JOIN category ON categoryid = category.id
	WHERE userid = :userid2
	ORDER BY article.id DESC LIMIT '.$offset.' , '.$limit.' )';

try{
    $stmt = $this -> db -> prepare($sql);

    $stmt -> bindParam(":userid", $userid);
    $stmt -> bindParam(":userid2", $userid);
    $stmt -> execute();

   $rowscount = $stmt -> rowCount();
   if($rowscount > 0){
      $users = $stmt -> fetchAll();
      //sorting users and articles;
      $user= $users[0];
//sort users articles;
        if($rowscount > 1){
            for($i = 1; $i < count($users); $i++){
               $userarticles[] = $users[$i];
            }
       }

    $users = array("user" => $user, "userarticles" => $userarticles);
    return $users;
    }else{
       return FALSE;
    }  
}
 catch(PDOException $e){
    echo $e -> getMessage();
     return false;
  }
//end of catch;
}
//end of getUsersArticles;

public function isLoggedIn(){
if(isset($_POST['enter'])){
   if($user = $this->isInDatabase($_POST['email'], $_POST['password'])){
     session_start();
     $_SESSION['userdata']= $user;
     $_SESSION['userid']= $user[0];
     $_SESSION['email']= $_POST['email'];
     $_SESSION['password']= $_POST['password'];
     session_write_close();
     return TRUE;
   }else {
      session_start();
       unset($_SESSION['userid']);
       unset($_SESSION['email']);
       unset($_SESSION['password']);
       unset($_SESSION['userdata']);
       session_write_close();
      return FALSE;
   }
 }
if(isset($_GET['logout'])){
      session_start();
       unset($_SESSION['userdata']);
       unset($_SESSION['userid']);
       unset($_SESSION['facebookid']);
       unset($_SESSION['email']);
       unset($_SESSION['password']);
      session_write_close();
       return FALSE;
 }
if(!isset($_SESSION)){
    session_start();
}
if(isset($_SESSION['password'])){
    if($user = $this->isInDatabase($_SESSION['email'], $_SESSION['password'])){
        $_SESSION['userdata']= $user;
        $_SESSION['userid']= $user[0];
        return TRUE;
    }else {
        unset($_SESSION['userid']);
        unset($_SESSION['email']);
        unset($_SESSION['password']);
        unset($_SESSION['userdata']);
        return FALSE;
    }
}

//facebook login;
if(isset($_SESSION['facebookid'])){
    if($user = $this->isFacebookidInDatabase($_SESSION['facebookid'])){
        $_SESSION['userdata']= $user;
        $_SESSION['userid']= $user[0];
        return TRUE;
    }else {
        unset($_SESSION['userid']);
        unset($_SESSION['email']);
        unset($_SESSION['password']);
        unset($_SESSION['userdata']);
        return FALSE;
    }
}

session_write_close();

//login from a recovered password mail;
if(isset($_GET['xpxwn'])){
   if($user = $this->isInDatabase($_GET['email'], $_GET['xpxwn'])){
     session_start();
     $_SESSION['userdata']= $user;
     $_SESSION['userid']= $user[0];
     $_SESSION['email']= $_GET['email'];
     $_SESSION['password']= $_GET['xpxwn'];
    session_write_close();
    return TRUE;
   }else {
      session_start();
       unset($_SESSION['userid']);
       unset($_SESSION['email']);
       unset($_SESSION['password']);
       unset($_SESSION['userdata']);
      session_write_close();
       return FALSE;
   }
 }
}
//end of login;

public  function isInDatabase($email,$password){
        if(!isset($_GET['xpxwn'])){
            $password = encodePassword($password);
        }

        $sql = '(SELECT user.id,firstname,mobile,profilepic
        ,role.name as rolename FROM user INNER JOIN role ON user.roleid = role.id
    	WHERE email= :email	AND password = :password)';
        try{
            $stmt = $this -> db -> prepare($sql);
            $stmt -> bindParam(":email", $email);
            $stmt -> bindParam(":password", $password);

            $stmt -> execute();
            $userdata = $stmt -> fetch();
        }
        catch(PDOException $e){
            echo $e -> getMessage();
        }
        if($userdata[0] > 0){
            //replacing user image;
            if(empty($userdata['profilepic'])){
                $userdata['profilepic'] = "/api/img/users/user.jpg";
            }
            return $userdata;
        }else{
            return FALSE;
        }

    }

//is facebookid in database;

public  function isFacebookidInDatabase($facebookid){

        $sql = '(SELECT user.id,firstname,mobile,profilepic
        ,role.name as rolename FROM user INNER JOIN role ON user.roleid = role.id
    	WHERE facebookid = :facebookid)';
        try{
            $stmt = $this -> db -> prepare($sql);
            $stmt -> bindParam(":facebookid", $facebookid);
            
            $stmt -> execute();
            $userdata = $stmt -> fetch();
        }
        catch(PDOException $e){
            echo $e -> getMessage();
        }
        if($userdata[0] > 0){
            return $userdata;
        }else{
            return FALSE;
        }

    }

//hasrole;


//get active users;
    public function getUsersByRole($roleid,$pgn,$amtperpage){
        $limit = ((!empty($pgn)) ? ($pgn*$amtperpage): $amtperpage);

        $sql = 'SELECT user.id,firstname,surname,profilepic,
                role.name as rolename, mobile,gender,
                location.name AS locationname,sublocation.name AS sublocationname,
                rolenote
	            FROM user INNER JOIN role ON user.roleid = role.id
	            INNER JOIN location ON location.id = locationid
	            INNER JOIN sublocation ON sublocation.id = sublocationid
	            WHERE roleid = :roleid
	            ORDER BY user.id LIMIT '.$pgn.','.$limit;

        try{
            $stmt = $this -> db -> prepare($sql);

            $stmt -> bindParam(":roleid", $roleid);
            $stmt -> execute();
            $users = $stmt -> fetchAll();
            if(!empty($users)){
                //substitute empty user images;
                for($i=0; $i<count($users); $i++){
                    if(empty($users[$i]['profilepic'])){
                        $users[$i]['profilepic'] = "/api/img/users/user.jpg";
                    }
                }
            }
            return $users;
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO GET USERS IN THEIR ROLES';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();
        }
    }


//get active users;
    public function getUsersByProperty($property, $value,$pgn,$amtperpage){

        $limit = ((!empty($pgn)) ? ($pgn*$amtperpage): $amtperpage);


        $presql = $sql = 'SELECT user.id,firstname,surname,profilepic,
                role.name as rolename, mobile,gender,
                location.name AS locationname,sublocation.name AS sublocationname,
                rolenote,cluster.name AS clustername
	            FROM user INNER JOIN role ON user.roleid = role.id
	            INNER JOIN location ON location.id = locationid
	            INNER JOIN sublocation ON sublocation.id = sublocationid
	            INNER JOIN clusteruser ON clusteruser.userid = user.id
	            INNER JOIN cluster ON clusteruser.clusterid = cluster.id
	            WHERE ';

        if(($property == 'wq') || $amtperpage == 0){
            $sql = $presql.' '.$value;
        }else{
            $sql = $presql.$property.' = :value ORDER BY user.id LIMIT '.$pgn. ' , '.$limit;
        }
        //echo($sql); exit;

        try{
            $stmt = $this -> db -> prepare($sql);

            $stmt -> bindParam(":value", $value);
            $stmt -> execute();
            $users = $stmt -> fetchAll();
            if(!empty($users)){
                //substitute empty user images;
                for($i=0; $i<count($users); $i++){
                    if(empty($users[$i]['profilepic'])){
                        $users[$i]['profilepic'] = "/api/img/users/user.jpg";
                    }
                }
            }
            return $users;

        }
        catch(PDOException $e){
            $error = $sql. $e -> getMessage().' SQL ERROR UNABLE TO GET USERS IN THEIR ROLES';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();
        }
    }


    //check property exists

    public function existsInTable($table,$property, $value){
        $sql = 'SELECT COUNT(*) FROM '.$table.' WHERE '.$property.'= :value ';

        try{
            $stmt = $this -> db -> prepare($sql);
            $stmt -> bindParam(":value", $value);

            $stmt -> execute();
            $rowscount = $stmt -> rowCount();
            if($rowscount > 0){
                return TRUE;
            }else{
                return FALSE;
            }
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO check property';
            $error = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit;
        }
    }
//end of hasrole;

    public function hasRole($rolename, $email){

    $sql = 'SELECT user.id FROM user
	INNER JOIN role ON user.roleid = role.id
	WHERE email= :email
	AND role.name = :rolename';

  try{
    $stmt = $this -> db -> prepare($sql);
    $stmt -> bindParam(":rolename", $rolename);
    $stmt -> bindParam(":email", $email);

    $stmt -> execute();
    $rowscount = $stmt -> rowCount();
   if($rowscount > 0){
     return TRUE;
   }else{
	 return FALSE;
       }
 }
  catch(PDOException $e){
     $error = 'SQL ERROR UNABLE TO GET THIS TO GET SMS UNIT COUNT';
     $error2 = $e -> getMessage();
     include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
     exit();

  }
 } 
//end of hasrole;


public function registerProUser($firstname, $surname,$email, $password,
$mobile, $gender, $dateofbirth, $dateofregistration, $about,
$locationid,$sublocationid, $school, $profilepic,$roleid,$rolenote,$public){

    //formatting and adding user product category;
    if(isset($_POST["categorycount"])){
        $categorycount = htmlspecialchars($_POST["categorycount"]);
        $categorystr = '';
        for($i=0; $i<$categorycount; $i++){
            $category = "category".$i;
            if(!empty($_POST[$category])){
                $categorystr .= $_POST[$category];
            }
        }
        if($categorystr == ''){
            $error = 'No product category assigned, please add user atleast a product categories';
            include $_SERVER['DOCUMENT_ROOT'].'/api/admin/adminregistration.html.php';
            exit;
        }

    }



    $sql = 'INSERT INTO user
        (firstname , surname , email ,
	 password , mobile, gender,
	 dateofbirth , dateofregistration ,
	 about ,locationid,sublocationid, school ,
	profilepic,roleid,rolenote,public)
	VALUES(:firstname, :surname,
	:email, :password ,
	:mobile, :gender,:dateofbirth ,
	:dateofregistration,  :about, :locationid,:sublocationid,
	:school, :profilepic, :roleid, :rolenote, :public )';

  try{
    $db = $this -> db;
    $stmt =  $db-> prepare($sql);
    $stmt -> bindParam(":firstname", $firstname);
    $stmt -> bindParam(":surname", $surname);
    $stmt -> bindParam(":email", $email);
    $stmt -> bindParam(":password", $password);
    $stmt -> bindParam(":mobile", $mobile);
    $stmt -> bindParam(":gender", $gender);
    $stmt -> bindParam(":dateofbirth", $dateofbirth);
    $stmt -> bindParam(":dateofregistration", $dateofregistration);
    $stmt -> bindParam(":about", $about);
    $stmt -> bindParam(":locationid", $locationid);
    $stmt -> bindParam(":sublocationid", $sublocationid);
    $stmt -> bindParam(":school", $school);
    $stmt -> bindParam(":profilepic", $profilepic);
    $stmt -> bindParam(":roleid", $roleid);
    $stmt -> bindParam(":rolenote", $rolenote);
    $stmt -> bindParam(":public", $public);

    $stmt -> execute();
    $userid = $db ->lastInsertId();
  }
   catch(PDOException $e){
     $error = 'Email Or Mobile Already In Use Try Another';
     $error2 = $e -> getMessage();
     include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
     exit();
   }

    //add product category;
    if(!empty($_POST["cluster-count"])){
            $cluster_count = $_POST["cluster-count"];
            $value_str = '';
            for($i=0; $i<$cluster_count; $i++){
                $cluster_postname = "cluster".$i;
                if(!empty($_POST[$cluster_postname])){
                    $c = htmlspecialchars($_POST[$cluster_postname]);
                    $value_str .= "(".$userid.",".$c."),";
                }
            }
            if($value_str == ''){
                $error = 'No cluster assigned, please add user atleast a cluster';
                include_once $_SERVER['DOCUMENT_ROOT'].'/api/admin/adminregistration.html.php';
                exit;
            }
            $strlen = (strlen($value_str))-1;
            $value_str = substr($value_str,0,$strlen);

        $clustersql = "INSERT INTO clusteruser (userid , clusterid)
                        VALUES".$value_str;


        try{
            $db = $this -> db;
            $stmt =  $db-> prepare($clustersql);
            $stmt -> execute();
        }
        catch(PDOException $e){
            $error = $e -> getMessage().' '.$clustersql.' '.'unable to associate to a cluster';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit;
        }
    }

    return TRUE;
}

    public function joinClusters($userid,$clusterids){
        //build values string;
        $value_str = "";
        for($i=0; $i<count($clusterids); $i++){
            $value_str .= ("(".$userid.",".$clusterids[$i] .'),');
        }
        $str_length = strlen($value_str) - 1;
        $value_str = substr($value_str,0,$str_length);

        $sql = 'INSERT INTO clusteruser (userid,clusterid) VALUES'.$value_str;
        try{
            $stmt = $this -> db -> prepare($sql);
            $stmt -> execute();
            $rowscount = $stmt->rowCount();
        }
        catch(PDOException $e){
            $error2 =  $e -> getMessage();
            $error = "SQL: error ".$error2;
            include_once $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();

        }
        if($rowscount > 0){
            return TRUE;
        }
        else{
            return FALSE;
        }
    }

//register user end;
public function registerUserTemporal($firstname, $surname,$email, $password,
                                 $mobile, $gender, $dateofbirth, $dateofregistration, $about,
                                 $locationid,$sublocationid, $school, $profilepic,$roleid,$rolenote,$public){
        $sql = 'INSERT INTO temporaryuser
        (firstname , surname , email ,
	 password , mobile, gender,
	 dateofbirth , dateofregistration ,
	 about ,locationid,sublocationid, school ,
	profilepic,roleid,rolenote,public)
	VALUES(:firstname, :surname,
	:email, :password ,
	:mobile, :gender,:dateofbirth ,
	:dateofregistration,  :about, :locationid,:sublocationid,
	:school, :profilepic, :roleid, :rolenote, :public )';

        try{
            $db = $this -> db;
            $stmt =  $db -> prepare($sql);
            $stmt -> bindParam(":firstname", $firstname);
            $stmt -> bindParam(":surname", $surname);
            $stmt -> bindParam(":email", $email);
            $stmt -> bindParam(":password", $password);
            $stmt -> bindParam(":mobile", $mobile);
            $stmt -> bindParam(":gender", $gender);
            $stmt -> bindParam(":dateofbirth", $dateofbirth);
            $stmt -> bindParam(":dateofregistration", $dateofregistration);
            $stmt -> bindParam(":about", $about);
            $stmt -> bindParam(":locationid", $locationid);
            $stmt -> bindParam(":sublocationid", $sublocationid);
            $stmt -> bindParam(":school", $school);
            $stmt -> bindParam(":profilepic", $profilepic);
            $stmt -> bindParam(":roleid", $roleid);
            $stmt -> bindParam(":rolenote", $rolenote);
            $stmt -> bindParam(":public", $public);

            $stmt -> execute();
            $userid = $db ->lastInsertId();
        }
        catch(PDOException $e){
            $error = 'Email  Already In Use Try Another';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit;
        }

    $subject = "Jonapwd Anambra Registration Confirmation";
    $message = "Thanks for your Interest in our forum, Here is your Confirmation Link, Click On this to confirm your registration
    https://www.jonapwdanambra.org.ng/api/user/index.php?confirmregister&xpxwd=".$password."&tempuserid=".$userid."&email=".$email."&name=".$firstname;
    $headers = "From: jonapwdsupport@jonapwdanambra.org.ng \r\n";

    if(mail($email,$subject,$message,$headers)){
        return true;
    }else{
        //if mail not sent delete data for further user's trial
        $sql2 = "DELETE FROM temporaryuser WHERE id = ".$userid;
        try{
            $db = $this -> db;
            $stmt =  $db -> prepare($sql2);
            $stmt->execute();
        }catch (PDOException $e){
            $error = 'data not deleted';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit;
        }
        return false;
    }

    }
//register user end;


//register user end;
    public function registerUserPermanent($tempuserid, $password){
        $sql = 'INSERT INTO user (firstname , surname , email ,password , mobile, gender,
        dateofbirth , dateofregistration , about ,locationid,sublocationid,
        school , profilepic,roleid,rolenote,public)
        SELECT firstname , surname , email ,password , mobile, gender,
        dateofbirth , dateofregistration , about ,locationid,sublocationid,
        school , profilepic,roleid,rolenote,public FROM temporaryuser
	    WHERE temporaryuser.id = :tempuserid AND temporaryuser.password = :password';

        try{
            $db = $this -> db;
            $stmt =  $db -> prepare($sql);
            $stmt -> bindParam(":tempuserid", $tempuserid);
            $stmt -> bindParam(":password", $password);

            $stmt -> execute();
            $userid = $db->lastInsertId();
        }
        catch(PDOException $e){
            $error = 'Email  Already In Use Try Another';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();
        }
        $this->joinClusters($userid,[1]);
        return TRUE;
    }
//register user end;

public function editUser($userid, $property, $value){
   $sql = 'UPDATE user SET '.$property. ' = :value
	   WHERE id = :userid';
 try{
    $stmt = $this -> db -> prepare($sql);   
    $stmt -> bindParam(":value", $value);
    $stmt -> bindParam(":userid", $userid);
    
    $stmt -> execute();
    $rowscount = $stmt -> rowCount();
  }
   catch(PDOException $e){
     $error = 'SQL ERROR UNABLE TO GET THIS TO GET SMS UNIT COUNT';
     $error2 = $e -> getMessage();
     include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
     exit();
   }
if($rowscount > 0){
   return TRUE;   
 }
 else{
   return FALSE;
 }
}
//end of edituser;

//password;
//passwordrecovery Must BE INCLUDED IN INDEX DIRECTLY CALLED;
//stops at sending mail to user and setting URL FOR LOCATION HEADER;

public function recoverPassword(){
if(isset($_POST['recoverpassword'])){
  $email=$_POST['email'];

   $sql = 'SELECT user.id , password FROM user
		WHERE email = :email';
try{
 $stmt = $this->db->prepare($sql);
 $stmt -> bindParam(":email", $email);

 $stmt -> execute();
 $row =   $stmt -> fetch();
  }
  catch(PDOException $e){
     $error = 'SQL ERROR UNABLE TO RESET PASSWORD';
     $error2 = $e -> getMessage();
     include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
     exit();
   }
  if($row[0] > 0){
     $userpwd = $row["password"];
     $userid = $row["id"];

   $url = 'http://'.$_SERVER['SERVER_NAME'].
'/api/user/index.php?email='.$email.'&xixdn='
.$userid.'&xpxwn='.$userpwd;

   $header= '"From:jonapwdsupport@jonapwdanambra.org.ng"."/r/n"';
   $to=$_POST['email'];
   $msg= '<p> CLICK HERE TO LOG IN AND RESET YOUR PASSWORD</p>  '.$url;
   $subject = 'Your Recovery Link';

//mail user the login url with changed password;
mail($to,$subject,$msg,$header);

    $output ='A Mail Has Been Sent To Your EMAIL NOW';
    $url = "Location: /user/index.php?uid=".$userid."&output=".$output;
    header($url);
    exit();
    }
    else{
        $error = 'This Email Is NOT REGISTERED WITH US.';
    	include $_SERVER['DOCUMENT_ROOT']. '/api/user/forms/recoverpassword.html.php';
        exit();
    }    
  }

}
// end of recoverpassword end;

//reset password;

public function resetPassword($id, $email, $oldpassword,$newpassword){
  $password = encodePassword($newpassword);
  $password2 = encodePassword($oldpassword);

$sql='UPDATE user SET
	password = :password WHERE email = :email AND password = :password2';
try{
$stmt = $this -> db -> prepare($sql);
   $stmt -> bindParam(":password", $newpassword);
   $stmt -> bindParam(":email", $email);
   $stmt -> bindParam(":password2", $oldpassword);

$stmt -> execute();
$rowscount = $stmt -> rowCount();
   }
    catch(PDOException $e){
     $error = 'SQL ERROR UNABLE MATCH PASSWORD';
     $error2 = $e -> getMessage();
     include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
     exit();
     }
if($rowscount > 0){
    return TRUE;
  }else{
      return FALSE;
     }


}
//END RESET PASSWORD;

//get ward users by sublocation;
    public function getUsersInsublocationByRole($sublocationid,$roleid){
        $sql = '(SELECT user.id, firstname, surname,
	   profilepic,role.name as rolename,sublocation.name as sublocationname
	   FROM user INNER JOIN sublocation ON user.sublocationid = sublocation.id
	   INNER JOIN role ON user.roleid = role.id
	   WHERE (user.sublocationid = :sublocationid AND role.id = :roleid))';
        try{
            $stmt = $this -> db -> prepare($sql);
            $stmt -> bindParam(":sublocationid", $sublocationid);
            $stmt -> bindParam(":roleid", $roleid);

            $stmt -> execute();
            $rowscount = $stmt -> rowCount();
            if($rowscount > 0){
                $users = $stmt -> fetchAll();
                return $users;
            }else{
                return FALSE;
            }
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO GET THIS TO GET SMS UNIT COUNT';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();

        }
    }
//end of get users in a location;

//get users by location by role;
    public function getUsersInlocationByRole($locationid,$roleid){
        $sql = '(SELECT user.id, firstname, surname,
	   profilepic,role.name as rolename,location.name as locationname
	   FROM user INNER JOIN location ON user.locationid = location.id
	   INNER JOIN role ON user.roleid = role.id
	   WHERE (user.locationid = :locationid AND role.id = :roleid))';
        try{
            $stmt = $this -> db -> prepare($sql);
            $stmt -> bindParam(":locationid", $locationid);
            $stmt -> bindParam(":roleid", $roleid);

            $stmt -> execute();
            $rowscount = $stmt -> rowCount();
            if($rowscount > 0){
                $users = $stmt -> fetchAll();
                return $users;
            }else{
                return FALSE;
            }
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO GET  location persons in the role';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();

        }
    }
//end of get users in a location;
//get user roles;

//get wards;
    public function getRoles(){
        $sql = '(SELECT id, name FROM role)';

        try{

            $stmt = $this -> db -> prepare($sql);

            $stmt -> execute();
            $rowscount = $stmt -> rowCount();
            if($rowscount > 0){
                $roles = $stmt -> fetchAll();
                return $roles;
            }else{
                return FALSE;
            }
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO GET roles';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();

        }
    }
//end of get roles
}
//end of user class;

?>