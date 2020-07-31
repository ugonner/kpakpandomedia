<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/api/includes/db/connect2.php';
class Cluster{

    protected $db;

    public function __construct(){
        $dbh = new Dbconn();
        $this -> db = $dbh->dbcon;
    }


    public function getClusters(){
        $sql = '(SELECT id, name FROM cluster ORDER BY name ASC)';

        try{

            $stmt = $this -> db -> prepare($sql);

            $stmt -> execute();
            $rowscount = $stmt -> rowCount();
            if($rowscount > 0){
                $clusters = $stmt -> fetchAll();
                return $clusters;
            }else{
                return FALSE;
            }
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO GET clusters';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();

        }
    }
//end of get categories;


    public function getOneCluster($clusterid){
        $sql = '(SELECT id AS clusterid, name AS clustername FROM cluster WHERE id = :clusterid)';

        try{

            $stmt = $this -> db -> prepare($sql);
            $stmt->bindParam(":clusterid", $clusterid);

            $stmt -> execute();
            $rowscount = $stmt -> rowCount();
            if($rowscount > 0){
                $cluster = $stmt -> fetchAll();
                return $cluster;
            }else{
                return FALSE;
            }
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO GET cluster';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();

        }
    }
//end of get categories;

    public function getCluster($clusterid){
        require_once $_SERVER['DOCUMENT_ROOT']. '/api/article/article.class.php';

        $cluster = array();
        $articles = array();

        $sql = '(SELECT cluster.id AS clusterid,cluster.name AS clustername,
       cluster.note AS clusternote FROM cluster
       WHERE cluster.id = :clusterid)';

        try{

            $stmt = $this -> db -> prepare($sql);

            $stmt -> bindParam(":clusterid", $clusterid);

            $stmt -> execute();
            $rowscount = $stmt -> rowCount();

            if($rowscount > 0){
                $cluster = $stmt -> fetch(PDO::FETCH_ASSOC);

                //get articles;
                $article = new article();
                $articles = $article->getarticlesByProperty("clusterid",$clusterid,24,0);
                return array("cluster"=> $cluster, "articles"=>$articles);
            }else{
                return FALSE;
            }
        }
        catch(PDOException $e){
            $error = 'SQL ERROR UNABLE TO GET cluster';
            $error2 = $e -> getMessage();
            include $_SERVER['DOCUMENT_ROOT'].'/api/includes/errors/error.html.php';
            exit();

        }
    }
//end of GET FOCAL AREA;

}
?>