/* This script and many more are available free online at
The JavaScript Source!! http://www.javascriptsource.com
Created by: Michael Futreal | http://michael.futreal.com/ */

jQuery.fn.vjustify=function () {
    var maxHeight=0;
    this.each(function () {
        if (this.offsetHeight>maxHeight) {
            maxHeight=this.offsetHeight;}
    });
    this.each(function () {
        $(this).height(maxHeight + "px");
        if (this.offsetHeight>maxHeight) {
            $(this).height((maxHeight-(this.offsetHeight-maxHeight))+"px");
        }
    });
};

