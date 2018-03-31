
// add this file to clients websites.
// if a change request attempt is detected we load the full dynamic scripting.
// this takes load off our server instead of running full dynamic script all the time.

// we look for a cookie on our 'ucm' installation page.
// this cookie is set as part of the redirect to the customers website.

// the user can cancel their change request popup and this removes the cookie.



var dtbaker_public_change_request = {
    url: '',
    hash: '',
    include_url: true,
    set_cookie: function(c_name,value,exdays) {
        var exdate=new Date();
        exdate.setDate(exdate.getDate() + exdays);
        var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
        document.cookie=c_name + "=" + c_value;
    },
    get_cookie: function(c_name) {
        var i,x,y,ARRcookies=document.cookie.split(";");
        for (i=0;i<ARRcookies.length;i++) {
            x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
            y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
            x=x.replace(/^\s+|\s+$/g,"");
            if (x==c_name) {
                return unescape(y);
            }
        }
    },
    cancel: function(){
        this.set_cookie("change_request","",1);
    },
    get_url_param: function(name) {
        return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
    },
    load_js: function(){
        var t = dtbaker_public_change_request;
        if(t.hash) {
            //alert("Change request active!");
            // load in our javascript and css for the change request features.
            var head= document.getElementsByTagName('head')[0];
            var script= document.createElement('script');
            script.type= 'text/javascript';
            script.src= t.url+t.hash+ (t.include_url ? (t.url.match(/\?/)?'&':'?')+'url='+encodeURIComponent(window.location.href) : '');
            head.appendChild(script);
        }
    },
    inject_js: function(source,callback){
        var headTag = document.getElementsByTagName("head")[0];
        var jqTag = document.createElement('script');
        jqTag.type = 'text/javascript';
        jqTag.src = source;
        if(typeof callback == 'function'){
            jqTag.onload = function(){
                headTag.removeChild(this); // todo - remove this if it causes problems in other browsers.
                callback();
            }
            jqTag.onreadystatechange = function () { // Same thing but for IE
                if (this.readyState == 'complete' || this.readyState == 'loaded'){
                    headTag.removeChild(this); // todo - remove this if it causes problems in other browsers.
                    callback();
                }
            };
        }
        headTag.appendChild(jqTag);
    },
    inject_css: function(source){
        var fileref=document.createElement("link");
        fileref.setAttribute("rel", "stylesheet");
        fileref.setAttribute("type", "text/css");
        fileref.setAttribute("href", source);
        var headTag = document.getElementsByTagName("head")[0];
        headTag.appendChild(fileref);
    },
    init: function(url){

        var t = this;
        // url for javascript to load onto this page.
        t.url = url;
        t.hash = t.get_url_param("change_request");
        if(t.hash){
            t.hash = t.hash.replace(/\W/g,'');
            t.set_cookie("change_request",t.hash,1);
            var newurl = window.location.href;
            newurl = newurl.replace(/\?change_request=\w+\&/,'?');
            newurl = newurl.replace(/\?change_request=\w+/,'');
            newurl = newurl.replace(/\&change_request=\w+/,'');
            window.location.href = newurl;
            return false;
        }
        if(!t.hash){
            t.hash = t.get_cookie("change_request");
        }

        if(t.hash){
            if(typeof jQuery=='undefined') {

                //document.write("<scr" + "ipt type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js\"></scr" + "ipt>");
                t.inject_js('https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js', t.load_js);

            } else {
                this.load_js();
            }
        }
        return true;
    }
};

