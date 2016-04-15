<html>
    <head>
        <style>
            .header{height: 55px;}
            .header-text{width: 350px;}
            .logo{width: 105px; position: absolute; top: 0; right: 0;}
            
            .row{position: relative; height: 180px;}
            .rowtopmargin {margin-top: 20px;}
            
            .alignleft{text-align: left;}
            .alignright{text-align: right;}
            
            
            
            .leftfieldset{width: 250px; position: absolute; top: 0; left: 0;}
            .rightfieldset{width: 250px; position: absolute; top: 0; right: 0;}
            .rowfieldset{}
            
            .fieldsetlegend {
                text-align: center;
                margin-top: 5px;
                font-weight: bold;
            }
            
            .fontsize10 {font-size: 10px;}
            .keybox {width: 20px; height: 15px; display: inline-block;}
            
            .borderall{border:1px solid #ccc;}
            .blackbg{background-color: #000000;}
            .redbg{background-color: #ff0000;}
            .greenbg{background-color: #008000;}
            
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                
                <div class="header-text alignleft">
                    
                    <strong>National Family Planning Dashboard Report</strong>
                    <br/>
                    <strong>Month: </strong> %1$s, %2$d
                </div>
                
                <div class="logo alignright">
                    <img  src="pdfrepo/coa.jpg" width="50px" height="53px"  /> 
                </div>
            </div>
            
            <div class="content">
                <hr>
                <div class="row">
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend">Number of trained health workers <br/> and 2015 targets</legend>
                        <img src="%5$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Method Mix, YTD %2$d</legend>
                        <img src="%6$s" width="250" height="167" />
                    </fieldset>
                </div>
                
                
                <div class="row rowtopmargin" style="height: 300px;">
                    <fieldset class="">
                        <legend class="fieldsetlegend">Monthly consumption*, %3$s – %4$s</legend>
                        <img src="%7$s" width="505" height="300" />
                        <div class="fontsize10">*Implants and injectables are examples of popular long-acting and short-acting 
                                methods and have been selected here to show general consumption trends of family planning. 
                        </div>
                    </fieldset>
                </div>
                
                <div class="row rowtopmargin" style="height: 300px;">
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend">Percent of facilities with an FP-<br/>trained health worker</legend>
                        <img src="%8$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Percent of facilities with a LARC-<br/>trained health worker</legend>
                        <img src="%9$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset style="position: absolute; top: 190; left: 0;">
                        <p class="fontsize10"><a class="keybox blackbg">&nbsp;</a> The black bars represent the national average for each indicator.</p>
                        <p class="fontsize10"><a class="keybox redbg">&nbsp;</a> The red bars represent the five lowest performing states for each indicator.</p>
                        <p class="fontsize10"><a class="keybox greenbg">&nbsp;</a> The green bars represent the top performing state for each indicator.</p>
                    </fieldset>
                </div>
                
                
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
                
                
                <div class="row rowtopmargin">
                    <fieldset class="leftfieldset">
                        <legend class="fieldsetlegend">Percent of facilities with a LARC <br/> trained health worker providing <br/> LARC</legend>
                        <img src="%12$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset class="rightfieldset">
                        <legend class="fieldsetlegend">Percent of FP facilities stocked out <br/> for 7 days</legend>
                        <img src="%13$s" width="250" height="167" />
                    </fieldset>
                    
                    <fieldset style="position: absolute; top: 190; left: 0;">
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