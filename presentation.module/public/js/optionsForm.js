BX.ready(function () {
    $('.js-init-select-refresh').change(function () {
        $(this).closest('form').submit();
    });

    setTimeout(function(){
        if (BX("bx_fd_input_'.strtolower($opt).'"))
            BX("bx_fd_input_'.strtolower($opt).'").onclick = window.BX_FD_EVENT;
    }, 200);
    window.BX_FD_ONRESULT = function(filename, filepath)
    {
        var oInput = $('.js-init-input-file');
        if (typeof filename == "object")
            oInput.val(filename.src);
        else
            oInput.val((filepath + "/" + filename));
    }

    $('.js-init-load-file').click(function () {
        window.BX_FD_EVENT();
    });
});
