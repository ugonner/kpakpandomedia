<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/api/cluster/cluster.class.php";

//get articles by categories;
if(isset($_GET["getcluster"])){
    $cid = $_GET["clusterid"];

    $cluster = new Cluster();
    if($cluster = $cluster->getCluster($cid)){
        $title = "Welcome to ".$cluster["cluster"]["clustername"]." section";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/cluster/cluster.html.php";
        exit();
    }else{
        $output = "No More activitiss In This Category Or It's Not Sanctioned";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/cluster/cluster.html.php";
        exit();
    }
}
//get more or next page;

//get articles by clusters;
if(isset($_GET["gabcl"])){
    $clusterid = $_GET["clid"];
    $amtperpage = 10;
    if(isset($_GET["pgn"])){
        $pgn = $_GET["pgn"];
    }else{
        $pgn = 0;
    }

    $sql = 'SELECT count(id) FROM article WHERE clusterid = :clid';

    $db = new Dbconn();
    $dbh = $db->dbcon;
    try{
        $stmt = $dbh -> prepare($sql);
        $stmt -> bindParam(":clid",$clid);

        $stmt -> execute();
        $counter = $stmt -> fetch();

    }
    catch(PDOException $e){
        $error = "Unable TO Count article";
        $error2 = $e -> getMessage();
        include_once $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
        exit;
    }
    $no_of_pages = ceil($counter[0] / $amtperpage);

    $cluster = new Cluster();
    $cluster = $cluster->getOneCluster($clusterid);
    $output = (!empty($_GET["output"])? $_GET["output"]: "");
    $article = new article();
    $title = " ".$cluster["clustername"]." Posts";
    if($articles = $article->getarticlesByProperty("clusterid",$clusterid,$amtperpage,$pgn)){
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/article/articles.html.php";
        exit;
    }else{
        $output = "No More articles In This Category Or It's Not Sanctioned";
        include_once $_SERVER["DOCUMENT_ROOT"]."/api/article/articles.html.php";
        exit;
    }
}

?>