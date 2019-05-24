jQuery(document).ready(function($) {

    var currentURL = document.location.href;

    if (/post.php/ig.test(currentURL)) {
        setPostHints();
    } else if (/admin.php\?page=gktpp-plugin-settings/ig.test(currentURL) && !/tab=info/i.test(currentURL)) {
        setAdminJS();
    }

    function validateHint(e) {
        var btn = document.getElementById('gktpp-submit-hints');
        var inputElem = document.getElementById('gktppURL');
    
        // btn.addEventListener("click", function(e) {
            if (inputElem.value.length === 0) {
                e.preventDefault();
                alert('Please enter a proper URL.');
            }
        // });
    }

    function setAdminJS() {
        var clickTarget = $('.gktpp-collapse-btn');
        clickTarget.on('click', function() {
            $(this).next('div').toggleClass('hide');
            $(this).find( $('button > span')).toggleClass('active');
        });

        // double check this fn later to make sure it works properly.

        var gktPPshowCacheWarning = function() {
            var location = document.getElementById('gktppHintLocation');
            var plugins = document.getElementById('gktppCachePlugins');
            var warning = document.getElementById('gktppBox');

            location.addEventListener('change', function() {
                if (location.value === 'HTTP Header' && plugins) {
                    warning.style.display = 'block';
                    warning.innerHTML = 'The plugin ' + plugins.innerHTML + ' caches HTTP headers, <br/> so I recommend that you load resource hints in your websites\'s &lt;head&gt; instead, and then refresh your cache!';
                }
            });
        }();



        $('input#gktpp-email').on("keyup", function(e) {
            return emailValidate(e);
        });

        $('input#gktpp-submit').on("click", function(e) {
            return emailValidate(e);
        });

        function emailValidate(e) {
            var email = document.getElementById("gktpp-email");
            var errorMSg = document.getElementById("gktpp-error-message");
            var mailformat = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/i;
        
            if (mailformat.test(email.value)) {
                errorMSg.style.display = "none";
                email.style.backgroundColor = "#cfebde";
                email.focus();
                return true;
            } else {
                e.preventDefault();
                errorMSg.style.display = "inline-block";
                email.style.backgroundColor = "#fce4e2";
                email.focus();
                return false;
            }
        }

    }

    







    // used on posts/pages
    function setPostHints() {
        var obj = {};
        var autoHintResetElem = $('input#gktppPageResetValue');
        var bulkApplyBtn = $("input#gktppApply");
        var updateHintsElem = $("input#gktppUpdateHints");
        var checkboxes = $("table#gktpp-post-table tbody tr th input:checkbox");
        var selectElem = $("select#gktpp-option-select");
    
        bulkApplyBtn.on("click", function() {
            obj.hintIDs = [];
            $.each(checkboxes, function() {
                if ($(this).is(":checked")) {
                    obj.hintIDs.push( $(this).val() );
                }
            });
            obj.action = selectElem.val();
            // showMsg();
            return updateElem(updateHintsElem);
        });
            
        $('input#gktppPageReset').on('click', function() {
            obj.reset = true;
            return updateElem(autoHintResetElem);
        });
    
        function updateElem(elem) {
            return elem.val(JSON.stringify(obj));
        }

        // function showMsg() {
        //     var pElem = $('p#gktppSavePostMsg');
        //     pElem.css({ 'visibility': 'visible' });
        // }

        function createHint() {
            var hintURL = $("input#gktppURL");
            var insertElem = $("input#gktppInsertedHints");
            var insertBtn = $("input#gktpp-submit-hints");
            var insertObj = {};

            insertBtn.on("click", function(e) {
                validateHint(e);
                insertObj.url = hintURL.val();

                $.each($("input.gktpp-radio:checked"), function() {
                    insertObj.type = $(this).val();
                });

                return insertElem.val( JSON.stringify(insertObj));
            });
        }
        createHint();
    }

    

});


