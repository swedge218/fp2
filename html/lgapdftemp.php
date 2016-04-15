<html>
    <head>
        <style>
            .header{height: 55px;font-family: Arial, Helvetica, sans-serif;}
            .header-text{width: 350px;font-family: Arial, Helvetica, sans-serif;}
            .logo{width: 105px; position: absolute; top: 0; right: 0;font-family: Arial, Helvetica, sans-serif;}
            
            .row{position: relative; height: 170px;font-family: Arial, Helvetica, sans-serif;}
            .rowtopmargin {margin-top: 20px;font-family: Arial, Helvetica, sans-serif;}
            
            .alignleft{text-align: left;font-family: Arial, Helvetica, sans-serif;}
            .alignright{text-align: right;font-family: Arial, Helvetica, sans-serif;}
            
            
            .footer{
    background-color:EEEEEE;
    width:90%;
    margin: 0 auto;
    font-family: Arial, Helvetica, sans-serif;
}
.footerleft{
   width:90%;
   position:absolute;
    left:0;
    font-family: Arial, Helvetica, sans-serif;
}
 tr:last-child > td { border-bottom: 0; font-family: Arial, Helvetica, sans-serif;}
.footerright{
    width:10%;
    position:absolute;
    right:0;
    font-family: Arial, Helvetica, sans-serif;
}
            .leftfieldset{width: 250px; position: absolute; top: 0; left: 0;font-family: Arial, Helvetica, sans-serif;}
            .rightfieldset{width: 250px; position: absolute; top:0; right: 0;font-family: Arial, Helvetica, sans-serif;}
            .rowfieldset{}
            
            
            .fieldsetlegend {
                text-align: center;
                
                font-weight: bold;
                font-family: Arial, Helvetica, sans-serif;
            }
            
            #alignMiddle{
                position:relative;
                margin-left:50px;
            }
            .fontsize10 {font-size: 10px;font-family: Arial, Helvetica, sans-serif;}
            .keybox {width: 20px; height: 15px; display: inline-block;font-family: Arial, Helvetica, sans-serif;}
            .fontsize5 {font-size: 6px;font-family: Arial, Helvetica, sans-serif;}
            .borderall{border:1px solid #ccc;font-family: Arial, Helvetica, sans-serif;}
            .blackbg{background-color: #000000;font-family: Arial, Helvetica, sans-serif;}
            .redbg{background-color: #ff0000;font-family: Arial, Helvetica, sans-serif;}
            .greenbg{background-color: #008000;font-family: Arial, Helvetica, sans-serif;}
            .bluebg{background-color: #0000ff;font-family: Arial, Helvetica, sans-serif;}
            .orangebg{background-color: #ffa500;font-family: Arial, Helvetica, sans-serif;}
            table, td, th {
    border: 1px solid #9ACD32;
}
table {
    border-collapse: collapse;
    font-family: Arial, Helvetica, sans-serif;
}


th {
    background-color: #9ACD32;
    color: white;
    font-family: Arial, Helvetica, sans-serif;
}
   

body{
    font-size:13px;
}
        </style>
    </head>
  
    
    <body>
        <div class="container">
            <div class="header">
                
                <div class="header-text alignleft">
                    
                    <strong>Family Planning Dashboard - LGA Report</strong>
                    <br/>
                    <strong>LGA: </strong> %7$s<br/>
					<strong>State: </strong> %6$s<br/>
                    <strong>Month: </strong> %1$s %2$d
                </div>
                
                <div class="logo alignright">
                    <img  src="pdfrepo/coa.jpg" width="55px" height="57px"  /> 
                </div>
            </div>
            
            <div class="content">
                <hr>
                <br/>
              <div class="row" style="height: 170px;  padding-bottom: 15px !important;">
                    <fieldset >
                        <legend class="fieldsetlegend">Facility summary, %5$s</legend>
                        <img src="%8$s" width="400" height="170" id="alignMiddle"/>
                    </fieldset>
                  
               
                 
                  
                </div>
              
                
                
                  <div class="row rowtopmargin" style="height: 177px; padding-bottom: 9px !important;">
                    <fieldset class="">
                        <legend class="fieldsetlegend">Monthly consumption* in %7$s, %3$s – %4$s</legend>
                        <img src="%9$s" width="450" height="175" align="center" style="padding-left:40px !important; " />
                        <div class="fontsize5">*Implants and injectables are examples of popular long-acting and short-acting 
                                methods and have been selected here to show general consumption trends of family planning. 
                        </div>
                    </fieldset>
                </div>
               
                firsttablebreak
                
            </div>
                
        </div>
        
    </body>
</html>