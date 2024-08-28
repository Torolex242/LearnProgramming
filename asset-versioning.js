(function() {
    function addVersionToUrl(url) {
        var timestamp = new Date().getTime();
        var separator = url.indexOf('?') !== -1 ? '&' : '?';
        return url + separator + 'v=' + timestamp;
    }

    function updateResourceUrls() {
        var resources = document.querySelectorAll('link[rel="stylesheet"], script[src]');
        resources.forEach(function(el) {
            if (el.tagName.toLowerCase() === 'link') {
                el.href = addVersionToUrl(el.href);
            } else if (el.tagName.toLowerCase() === 'script') {
                el.src = addVersionToUrl(el.src);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateResourceUrls);
    } else {
        updateResourceUrls();
    }
})();