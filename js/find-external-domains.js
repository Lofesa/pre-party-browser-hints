(function() {
    var scripts = document.getElementsByTagName('script');
    var urls = [];
    var host = document.location.origin;

    function sanitizeURL(url) {
        return url.replace(/[\[\]\{\}\<\>\'\"\\(\)\*\+\\^\$\|]/g, '');
    }

    function findResourceSources() {
        var resources = window.performance.getEntriesByType('resource');

        for (var i = 0; i < resources.length; i++) {
            var newStr = resources[i].name.split('/');
            var protocolAndDomain = newStr[0] + '//' + newStr[2];

            if (protocolAndDomain !== host && urls.indexOf(protocolAndDomain) === -1) {
                urls.push(sanitizeURL(protocolAndDomain));
            }
        }
    }

    // if this js code gets cached in another file, prevent it from firing every page load.
    if (/find-external-domains.js/i.test(scripts[scripts.length - 1].src)) {
        setTimeout(function() {
            findResourceSources();
            var xhr = new XMLHttpRequest();
            xhr.open('POST', host + '/wp-admin/admin-ajax.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            console.log(urls);
            console.log( post.postID );
            xhr.send('action=gktpp_post_domain_names&urls=' + JSON.stringify(urls) + '&postID=' + post.postID);
        }, 1000);
    }

})();