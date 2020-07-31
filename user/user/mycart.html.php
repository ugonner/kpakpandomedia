<?php include_once $_SERVER['DOCUMENT_ROOT'].'/api/inc/htmls/header.html.php'?>

<div class="container-fluid">

    <div class="row">
    <div class="col-md-3">
        <div class="row">
            <div class="col-md-12">
                <?php include_once $_SERVER['DOCUMENT_ROOT'].'/api/includes/htmlpages/leftbar.html.php';?>
            </div>
        </div>
    </div>

    <div class='col-md-6'>
        <div class="row">
            <h4 class="text-center page-header">My Cart!</h4>
            <?php if(isset($mycart)):?>
                <?php $total = 0;?>
            <?php for($i=0; $i < count($mycart); $i++):?>
                    <?php $total += $mycart[$i]["price"];?>
                <div style='background: #434343; border: 1px solid black;'>
                    <div style='width: 20%; display:inline-block;'><?php echo $i+1; ?>
                        <img class='img-circle' src='<?php echo $mycart[$i]["productimagedisplayname"]; ?>' style='width:45%; height: 40px;'>
                    </div>
                    <div style='width: 50%; display:inline-block; background: #323232;;'>
                        <a style="color: cyan;" href="/api/product/?gpid=<?php echo $mycart[$i]['id']; ?>&prdtid=<?php echo $mycart[$i]['productidentifier']; ?>">
                           <h6 class="text-capitalize"><b></b><?php echo $mycart[$i]['title']; ?></b></h6>
                        </a>
                    </div>
                    <div style='width: 18%;display:inline-block;'>
                        N<?php echo $mycart[$i]['price']; ?>
                        <a href="/api/product/?removefromcart&pid=<?php echo $mycart[$i][0]; ?>">
                            <button type='button' style='background: transparent; border: 0;'>
                                <span style='color:red;'>Remove</span>'
                            </button>
                        </a>
                    </div>
                </div>
            <?php endfor;?>
            <div style="border: 2px solid gray; background-color: saddlebrown;">
                <h5 style="padding: 5px;" class="text-right">Your Total Is N<?php echo $total; ?></h5>
                <h5 style="padding: 5px;" class="text-right">
                    <a style="color: #ffffff;" href="/api/product/?requestsupply">
                        <b>Request For Supply</b>
                    </a>
                </h5>
            </div>
            <?php else: ?>
            <div style=" border: 40px solid #000000;">
                <div style="background-image: url("/api/img/emptycart4.jpg") 100% 100%; background-repeat: no-repeat;
                     ">
                    <img src="/api/img/emptycart4.jpg" style="width: 100%; height: 100%; position: relative;">
                    <h3 style="position: absolute; top: 40%; color: darkgrey;">
                        <i><b><?php echo($output);?></b></i>
                    </h3>
                </div>
            </div>
            <?php endif;?>

        </div>
    </div>
    <div class="col-md-3">
        <div class="row">
            <div class="col-md-12">
                <?php include_once $_SERVER['DOCUMENT_ROOT'].'/api/includes/htmlpages/rightbar.html.php';?>
            </div>
        </div>
    </div>
</div>
</div>


<?php include $_SERVER['DOCUMENT_ROOT'].'/api/includes/htmlpages/footer.html.php'?>
</body>
</html>