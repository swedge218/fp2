<html>
    <head>
        <style>
            
            .header{height: 55px; font-family: Arial, Helvetica, sans-serif;}
            .header-text{width: 350px;font-family: Arial, Helvetica, sans-serif;}
            .logo{width: 105px; position: absolute; top: 0; right: 0;font-family: Arial, Helvetica, sans-serif;}
            
            .row{position: relative; height: 170px;font-family: Arial, Helvetica, sans-serif;}
            .rowtopmargin {margin-top: 20px;font-family: Arial, Helvetica, sans-serif;}
            .rowtopmargincustom{
                margin-top: 10px;font-family: Arial, Helvetica, sans-serif;
            }
            #topmost{margin-top:-7px !important;}
            #downward{height:135px;}
            .alignleft{text-align: left;font-family: Arial, Helvetica, sans-serif;}
            .alignright{text-align: right;font-family: Arial, Helvetica, sans-serif;}
            #alignMiddle{
                position:relative;
                margin-left:50px;
            }
            
            
            .leftfieldset{width: 250px; position: absolute; top: 0; left: 0;font-family: Arial, Helvetica, sans-serif;}
            .rightfieldset{width: 250px; position: absolute; top:0; right: 0;font-family: Arial, Helvetica, sans-serif;}
            .rowfieldset{}
            
            
            .fieldsetlegend {
                text-align: center;
                font-family: Arial, Helvetica, sans-serif;
                font-weight: bold;
            }
            
            .fontsize10 {font-size: 10px;font-family: Arial, Helvetica, sans-serif;}
            .keybox {width: 20px; height: 15px; display: inline-block;font-family: Arial, Helvetica, sans-serif;}
            
            .borderall{border:1px solid #ccc;font-family: Arial, Helvetica, sans-serif;}
            .blackbg{background-color: #000000;font-family: Arial, Helvetica, sans-serif;}
            .redbg{background-color: #ff0000;font-family: Arial, Helvetica, sans-serif;}
            .greenbg{background-color: #008000;font-family: Arial, Helvetica, sans-serif;}
            .bluebg{background-color: #0000ff;}
            .orangebg{background-color: #ffa500;}
            table, td, th {
    border: 1px solid #9ACD32;
    font-family: Arial, Helvetica, sans-serif;
}
table {
    border-collapse: collapse;
    font-family: Arial, Helvetica, sans-serif;
}
 .fontsize5 {font-size: 6px;font-family: Arial, Helvetica, sans-serif;}
body{
    font-size:13px;
}
th {
    background-color: #9ACD32;
    color: white;
    font-family: Arial, Helvetica, sans-serif;
}
            
        </style>
    </head>
  
    
    <body>
        <div class="container">
            <div class="header">
                
                <div class="header-text alignleft">
                    
                    <strong>Family Planning Dashboard - State Report</strong>
                    <br/>
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
                <div class="row">
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend">Facility coverage summary, %5$s</legend>
                        <img src="%7$s" width="250" height="167" />
                    </fieldset>
                  &nbsp;&nbsp;
               
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Facility training summary, %5$s</legend>
                        <img src="%8$s" width="250" height="165" />
                    </fieldset>
                  
                </div>
                <br/><br/>
                
                
                  <div class="row" style="height: 174px;">
                    <fieldset class="">
                        <legend class="fieldsetlegend">Monthly consumption* in %6$s, %3$s – %4$s</legend>
                        <img src="%9$s" width="405" height="174" id="alignMiddle"/>
                        <div class="fontsize5">*Implants and injectables are examples of popular long-acting and short-acting 
                                methods and have been selected here to show general consumption trends of family planning. 
                        </div>
                    </fieldset>
                </div>
                <br/><br/>
                 <div class="row" style="heigh:160px;top:25;">
                    <fieldset class="">
                         <legend class="fieldsetlegend">LGAs with lowest facility coverage</legend>
                        <table>
                            <thead>
                                <th nowrap="nowrap">%34$s with FP-trained HWs</th>
                                <th nowrap="nowrap">%34$s with LARC-trained HWs</th>
                                <th nowrap="nowrap">%34$s providing FP</th>
                                <th nowrap="nowrap">%34$s providing LARC</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>%14$s</td>
                                    <td>%15$s</td>
                                    <td>%16$s</td>
                                    <td>%17$s</td>
                                </tr>
                                <tr>
                                    <td>%18$s</td>
                                    <td>%19$s</td>
                                    <td>%20$s</td>
                                    <td>%21$s</td>
                                </tr>
                                <tr>
                                    <td>%22$s</td>
                                    <td>%23$s</td>
                                    <td>%24$s</td>
                                    <td>%25$s</td>
                                </tr>
                                <tr>
                                    <td>%26$s</td>
                                    <td>%27$s</td>
                                    <td>%28$s</td>
                                    <td>%29$s</td>
                                </tr>
                                <tr>
                                    <td>%30$s</td>
                                    <td>%31$s</td>
                                    <td>%32$s</td>
                                    <td>%33$s</td>
                                </tr>
                            </tbody>
                            
                        </table>
                    </fieldset>
                </div>
               
                <br/><br/>
                <div class="row rowtopmargin" id="downward">
                    <fieldset style="position: absolute; top:0 !important; left: 0;">
                        <p class="fontsize10"><a class="keybox blackbg">&nbsp;</a> The black bars represent the state average for each indicator.</p>
                        <p class="fontsize10"><a class="keybox redbg">&nbsp;</a> The red bars represent the five lowest performing LGAs for each indicator.</p>
                        <p class="fontsize10"><a class="keybox greenbg">&nbsp;</a> The green bars represent the top performing LGA for each indicator.</p>
                    </fieldset>
                </div>
                
                <div class="row rowtopmargincustom" style="height: 170px;" id="topmost" style="top:0 !important;">
                    
                   
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend" >Percent of facilities with an FP trained<br/>health worker providing FP</legend>
                        <img src="%10$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Percent of facilities with a LARC trained<br/>health worker providing LARC</legend>
                        <img src="%11$s" width="250" height="167" />
                    </fieldset>
                    
                    
                </div>
              <br/>
                <div class="row rowtopmargin" style="height: 170px;">
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend" >Percent of FP facilities stocked out<br/>of FP commodities for 7 days</legend>
                        <img src="%12$s" width="250" height="167" />
                    </fieldset>
                  &nbsp;&nbsp;
               
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend" >Percent of LARC facilities stocked<br/>out of implants</legend>
                        <img src="%13$s" width="250" height="167" />
                    </fieldset>
                  
                   <fieldset style="position: absolute; top:198;left: 0;">
                       <legend class="fieldsetlegend">ACTION ITEMS</legend>
                        <ul>
                            <li>Direct trainings to LGAs with lowest percent of facilities with trained health workers.</li>
                            <li>Follow up with and direct supervision or on-the-job training to facilities with trained health workers that are not providing services.</li>
                            <li>Contact LGAs that are experiencing stock outs and send products to LGA stores when possible, or prepare products for pick up by LGA or facility staff.</li>
                        </ul>
                    </fieldset>
                </div>
                 
                
                
            </div>
                
        </div>
        
    </body>
</html>