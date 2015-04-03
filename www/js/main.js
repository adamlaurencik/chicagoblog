function adjustHeight(el){
    if(el.scrollHeight > el.clientHeight){
         el.style.height=(el.scrollHeight)+"px";
    }
};

$(function () {
    var e=document.getElementsByTagName("textarea");
   for (i = 0; i < e.length; i++) { 
    adjustHeight(e[i]);
};
     $.nette.init();
     

});



