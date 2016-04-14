/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


   $('.infowrap').click(function(){
        $(this).siblings(".infodiv").animate({
            opacity: 1,
            height: "toggle",
            width: "toggle"
          }, 200, function() {
            // Animation complete.
          });
          //console.log('click function');
        return false;
    }); 


//function showInfo(anchor){
//    //$(this).next(".infodiv").animate({
//        $(anchor).find(".infodiv").animate({
//            opacity: 1,
//            height: "toggle",
//            width: "toggle"
//          }, 200, function() {
//            // Animation complete.
//          });
//        return false;
//}
