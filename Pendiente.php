<?php 
include_once "menu.html";
?>

<script> 
    // Definir la variable en JS 
    const nuevoTexto = "Solicitar salida"; 
    
    // Cambiar el contenido del h1 usando la variable 
    document.getElementById("titulo").textContent = nuevoTexto; 
</script>


<style>
    .glyphicon-lg
{
    font-size:4em
}
.info-block
{
    border-right:5px solid #E6E6E6;margin-bottom:25px
}
.info-block .square-box
{
    width:70px;min-height:70px;margin-right:22px;text-align:center!important;background-color:#676767;padding:20px 0
}
.info-block.block-info
{
    border-color:#20819e
}
.info-block.block-info .square-box
{
    background-color:#20819e;color:#FFF
}
</style>
<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
<!------ Include the above in your HEAD tag ---------->

<div class="container">
	<div class="row">
		<h2>Filtered List</h2>
        <div class="col-lg-12">
            <input type="search" class="form-control" id="input-search" placeholder="Search Attendees..." >
        </div>
        <br>
        <div class="searchable-container">
            <div class="items col-xs-12 col-sm-6 col-md-6 col-lg-6 clearfix">
               <div class="info-block block-info clearfix">
                    <div class="square-box pull-left">
                        <span class="glyphicon glyphicon-user glyphicon-lg"></span>
                    </div>
                    <h5>Company Name</h5>
                    <h4>Name: Tyreese Burn</h4>
                    <p>Title: Manager</p>
                </div>
            </div>
            <div class="items col-xs-12 col-sm-12 col-md-6 col-lg-6 clearfix">
               <div class="info-block block-info clearfix">
                    <div class="square-box pull-left">
                        <span class="glyphicon glyphicon-user glyphicon-lg"></span>
                    </div>
                    <h5>Company Name</h5>
                    <h4>Name: Brenda Tree</h4>
                    <p>Title: Manager</p>
                </div>
            </div>
            <div class="items col-xs-12 col-sm-12 col-md-6 col-lg-6">
               <div class="info-block block-info clearfix">
                    <div class="square-box pull-left">
                        <span class="glyphicon glyphicon-user glyphicon-lg"></span>
                    </div>
                    <h5>Company Name</h5>
                    <h4>Name: Glenn Pho shizzle</h4>
                    <p>Title: Manager</p>
                </div>
            </div>
            <div class="items col-xs-12 col-sm-12 col-md-6 col-lg-6">
               <div class="info-block block-info clearfix">
                    <div class="square-box pull-left">
                        <span class="glyphicon glyphicon-user glyphicon-lg"></span>
                    </div>
                    <h5>Company Name</h5>
                    <h4>Name: Brian Hoyies</h4>
                    <p>Title: Manager</p>
                </div>
            </div>
        </div>
	</div>
</div>