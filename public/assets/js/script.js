function upload_hinhanh(path = ''){
    $(".dinhkem").change(function() {
        var formData = new FormData($("#dinhkemform")[0]);$("#progressbar").show();
        $.ajax({
            url: path, type: "POST",
            cache: false, contentType: false,
            data: formData, processData:false,
            xhr: function(){
                //upload Progress
                var xhr = $.ajaxSettings.xhr();
                if(xhr.upload) {
                    xhr.upload.addEventListener('progress', function(event) {
                        var percent = 0;
                        var position = event.loaded || event.position;
                        var total = event.total;
                        if (event.lengthComputable) {
                            percent = Math.ceil(position / total * 100);
                        }
                        //update progressbar
                        $(".progress .progress-bar").css("width", + percent +"%");
                        $(".progress .progress-bar").text(percent +"%");
                        if(percent == 100){
                            $("#progressbar").fadeOut();
                        }
                    }, true);
                }
                return xhr;
            },
            success: function(datas) {
                if(datas=='Failed'){
                    alert('Lỗi không thể Upload hình ảnh.');
                } else {
                    $("#list_hinhanh").prepend(datas);
                    $('.draggable-element').arrangeable();
                    popup_images();delete_hinhanh();
                    //editImage();
                }
            },
            cache: false, contentType: false, processData: false
        }).fail(function() {
            alert('Lỗi không thể Upload hình ảnh.');
        });
    });
}
function upload_files(path){
    $(".dinhkem_files").change(function() {
      var formData = new FormData($("#dinhkemform")[0]);$("#progressbar").show();
      $.ajax({
        url: path, type: "POST",
        cache: false, contentType: false,
        data: formData, processData:false,
        xhr: function(){
                //upload Progress
                var xhr = $.ajaxSettings.xhr();
                if(xhr.upload) {
                    xhr.upload.addEventListener('progress', function(event) {
                        var percent = 0;
                        var position = event.loaded || event.position;
                        var total = event.total;
                        if (event.lengthComputable) {
                            percent = Math.ceil(position / total * 100);
                        }
                        //update progressbar
                        $(".progress .progress-bar").css("width", + percent +"%");
                        $(".progress .progress-bar").text(percent +"%");
                        if(percent == 100){
                            $("#progressbar").fadeOut();
                        }
                    }, true);
                }
                return xhr;
            },
          success: function(datas) {
              if(datas=='Failed'){
                  alert('Lỗi không thể Upload tập tin.');
              } else {
                $("#list_files").prepend(datas);delete_file();
                $('.draggable-element').arrangeable();
              }
          },
          cache: false, contentType: false, processData: false
        }).fail(function() {
            alert('Lỗi không thể Upload tập tin.');
        });
    });
}
function delete_file(){
    var link_delete; var _this;
    $(".delete_file").click(function(){
        link_delete = $(this).attr("href"); _this = $(this);
        $.ajax({
            url: link_delete,
            type: "GET",
            success: function(datas) {
                _this.parents("div.items").fadeOut("slow", function(){
                    $(this).remove();
                });
            }
        }).fail(function() {
            alert('Không thể xoá.');
        });
    });
}
function delete_hinhanh(){
    var link_delete; var _this;
    $(".delete_file").click(function(){
        link_delete = $(this).attr("href"); _this = $(this);
        $.ajax({
            url: link_delete,
            type: "GET",
            success: function(datas) {
                _this.parents("div.items").fadeOut("slow", function(){
                $(this).remove();
            });
            }
        }).fail(function() {
            //alert('Không thể xoá.');
            _this.parents("div.items").fadeOut("slow", function(){
                $(this).remove();
            });
        });
    });
}
function chontinh(app_url){
    $("#address_1").change(function(){
        $.get(app_url + 'admin/danh-muc/dia-chi/get/'+$(this).val(), function(tinh){
            $("#address_2").html(tinh);chonhuyen();
        });
    });
}

function chonhuyen(app_url){
    $("#address_2").change(function(){
        $.get(app_url +'admin/danh-muc/dia-chi/get/'+$(this).val(), function(huyen){
            $("#address_3").html(huyen);
        });
    });
}
function popup_images(){
    $('.image-popup').magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        mainClass: 'mfp-fade',
        gallery: {
            enabled: true,
            navigateByImgClick: true,
            preload: [0,1] // Will preload 0 - before current, and 1 after the current image
        }
    });
}

function convertToSlug(str) {
  str = str.replace(/^\s+|\s+$/g, ''); // trim
  str = str.toLowerCase();
  // remove accents, swap ñ for n, etc
  var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;";
  var to   = "aaaaaeeeeeiiiiooooouuuunc------";
  for (var i=0, l=from.length ; i<l ; i++) {
    str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
  }
  str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
    .replace(/\s+/g, '-') // collapse whitespace and replace by -
    .replace(/-+/g, '-'); // collapse dashes
  return str;
}

function DonViCungCapAutoComplete(path){
    $("#donvicungcap").autocomplete({
        serviceUrl: path,
        dataType: 'json',
        paramName: 'search',
        type: "GET",
        onSelect: function (suggestion) {
            $(this).val(suggestion.data);
        }
    });
}
