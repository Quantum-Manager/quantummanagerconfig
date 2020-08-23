document.addEventListener("DOMContentLoaded", function(event) {

    let element = '';
    let inputFileAll = document.querySelectorAll("input[name=importjson]");

    document.querySelector('.btn-import').addEventListener('click', function(e) {
        e.preventDefault();
        let confirm_config = confirm(window.QuantummanagerConfig.alert);

        if(!confirm_config) {
            return;
        }

        element = this.getAttribute('data-element');
        document.querySelector('input[name=importjson]').click();
        return false;
    });

    for (let i = 0; i < inputFileAll.length; i++) {
        inputFileAll[i].addEventListener('change', function () {
            handleFiles(this.files);
        }, false);
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        files = [...files];
        files.forEach(uploadFile);
    }

    function uploadFile(file, i) {
        let fd = new FormData();
        fd.append("params", file);
        let xhr = new XMLHttpRequest();
        xhr.open('POST', '/administrator/index.php?option=com_ajax&plugin=quantummanagerconfig&group=system&task=import&element=' + element + '&format=json', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                let percentComplete = (e.loaded / e.total) * 100;
            }
        };

        xhr.onload = function() {
            if (this.status === 200) {
                location.reload();
            } else {
                location.reload();
            }
        };
        xhr.send(fd);
    }

});


