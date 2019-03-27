<div id="fb-root"></div>
<script type="text/javascript">
window.fbAsyncInit = function() {
    FB.init({
        appId: '<?php echo esc_attr( $app_id ); ?>',
        status: true,
        cookie: true,
        xfbml: true
    });
};
// Load the FB SDK Asynchronously
(function(d){
    var js, id = "facebook-jssdk"; if (d.getElementById(id)) {return;}
    js = d.createElement("script"); js.id = id; js.async = true;
    js.src = "//connect.facebook.net/en_US/all.js";
    d.getElementsByTagName("head")[0].appendChild(js);
}(document));
</script>

