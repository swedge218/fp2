<html>
    <head>
        <style>
            .header{height: 55px;font-family: Arial, Helvetica, sans-serif;}
            .header-text{width: 350px;font-family: Arial, Helvetica, sans-serif;}
            .logo{width: 105px; position: absolute; top: 0; right: 0;font-family: Arial, Helvetica, sans-serif;}
            
            .row{position: relative; height: 180px;font-family: Arial, Helvetica, sans-serif;}
            .rowtopmargin {margin-top: 20px;font-family: Arial, Helvetica, sans-serif;}
            
            .alignleft{text-align: left;font-family: Arial, Helvetica, sans-serif;}
            .alignright{text-align: right;font-family: Arial, Helvetica, sans-serif;}
            
            
            
            .leftfieldset{width: 250px; position: absolute; top: 0; left: 0;font-family: Arial, Helvetica, sans-serif;}
            .rightfieldset{width: 250px; position: absolute; top: 0; right: 0;font-family: Arial, Helvetica, sans-serif;}
            .rowfieldset{}
            
            .fieldsetlegend {
                text-align: center;
               
                font-weight: bold;
                font-family: Arial, Helvetica, sans-serif;
            }
           
            .fontsize10 {font-size: 10px;font-family: Arial, Helvetica, sans-serif;}
            .fontsize5 {font-size: 6px;font-family: Arial, Helvetica, sans-serif;}
            .keybox {width: 20px; height: 15px; display: inline-block;font-family: Arial, Helvetica, sans-serif;}
            
            .borderall{border:1px solid #ccc;font-family: Arial, Helvetica, sans-serif;}
            .blackbg{background-color: #000000;font-family: Arial, Helvetica, sans-serif;}
            .redbg{background-color: #ff0000;font-family: Arial, Helvetica, sans-serif;}
            .greenbg{background-color: #008000;font-family: Arial, Helvetica, sans-serif;}
            
            body{
                font-size:12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                
                <div class="header-text alignleft">
                    
                    <strong>National Family Planning Dashboard Report</strong>
                    <br/>
                    <strong>Month: </strong> %1$s %2$d
                </div>
                
                <div class="logo alignright">
                    <img  src="pdfrepo/coa.jpg" width="55px" height="57px"  /> 
                </div>
            </div>
            
            <div class="content">
                <hr><br/>
               
                <div class="row" style="height:155px !important;" >
                    <fieldset class="leftfieldset" >
                        <legend class="fieldsetlegend">Number of trained health workers <br/> and 2015 targets</legend>
                       <br/>
                        <span align="center" style="margin-left:25;margin-top:-40px !important;font-size:7px;">2015 LARC target: 5,500, &nbsp;&nbsp;Gap to reach 2015 LARC target: %22$s.</span>
                        <br/>
                        <img src="%5$s" width="250" height="145" style="padding-bottom:7px !important;"/>
                        
                           <br/>
                        
                    </fieldset>
                    
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Estimated percent of clients <br/>by method, %1$s %2$d</legend>
                        <img src="%6$s" width="250" height="145" />
                        <div class="fontsize5">
                            Female Condoms: %14$s%21$s  |  Male Condoms: %15$s%21$s | Injectables: %16$s%21$s |Implants: %17$s%21$s | IUCDs: %18$s%21$s | Oral Pills: %19$s%21$s | Sterilization: %20$s%21$s <br/><br/>
                            *This chart estimates the percentage of clients accessing each family planning method in the current month, based on commodities consumed. All clients were assumed to have received one commodity each, except condom clients, who were assumed to receive ten. 
                        </div>
                    </fieldset>
                </div>
                <br/><br/>
                
                <div class="row" style="margin-top:15px !important; padding-top:15px !important;">
                    <fieldset class="">
                        <legend class="fieldsetlegend">Monthly consumption*, %3$s – %4$s</legend>
                        <img src="%7$s" width="450" height="165" align="center" style="padding-left:40px !important; "/>
                        <div class="fontsize5">*Implants and injectables are examples of popular long-acting and short-acting 
                                methods and have been selected here to show general consumption trends of family planning. 
                        </div>
                    </fieldset>
                </div>
                <br/><br/>
                    <div style="" class="row">
                    <fieldset class="leftfieldset" style="width:230px !important;">
                        <legend class="fieldsetlegend">Percent of facilities with an FP-<br/>trained health worker</legend>
                        <img src="%8$s" width="230" height="158" />
                    </fieldset>
                    
                    <fieldset class="rightfieldset" style="width:230px !important;">
                        <legend class="fieldsetlegend">Percent of facilities with a LARC-<br/>trained health worker</legend>
                        <img src="%9$s" width="230" height="158" />
                    </fieldset>
                    </div><br/>
                   
                
                
                <div class="row" style="height: 100px !important; ">
                     <fieldset style="">
                        
                        <p class="fontsize10"><a class="keybox blackbg">&nbsp;</a> The black bars represent the national average for each indicator.</p>
                        <p class="fontsize10"><a class="keybox redbg">&nbsp;</a> The red bars represent the five lowest performing states for each indicator.</p>
                        <p class="fontsize10"><a class="keybox greenbg">&nbsp;</a> The green bars represent the top performing state for each indicator.</p>
                    </fieldset>
                </div>
                <br/>
                
                <div class="row rowtopmargin">
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend">Percent of facilities providing FP</legend>
                        <img src="%10$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Percent of facilities providing LARC</legend>
                        <img src="%11$s" width="250" height="167" />
                    </fieldset>
                </div>
                
                <br/>
                <div class="row rowtopmargin">
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend">Percent of facilities with a LARC <br/> trained health worker providing <br/> LARC</legend>
                        <img src="%12$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Percent of FP facilities stocked out <br/> for 7 days</legend>
                        <img src="%13$s" width="250" height="167" />
                    </fieldset>
                    
                </div>
                
                <div class="row">
                    <fieldset style="position: absolute; top: 30; left: 0;">
                         <legend class="fieldsetlegend">ACTION ITEMS</legend>
                        <ul>
                            <li>Direct training resources to states with lowest percentage of facilities with trained HWs.</li>
                            <li>Investigate states with low LARC performance (states with a low percentage of LARC trained HWs providing LARC). Explore what training model is used in this state and how it can be made more effective.</li>
                            <li>Investigate states with high stock out rates to determine if distribution is happening frequently enough, if sufficient stock is provided, and if all facilities are included in the distribution system.</li>
                        </ul>
                    </fieldset>
                    </div>
            </div>
                
        </div>
        
    </body>
</html>