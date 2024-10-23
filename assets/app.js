import './vendor/bootstrap/dist/css/bootstrap.min.css';
import './jquery-ui.min.js';
import './jquery-ui.min.css';
import './jquery-ui.theme.min.css';

/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// Tableau pour stocker les fichiers sélectionnés
var selectedFiles = [];
var deletedFiles = [];
$(document).on('click' , '.create-trick' , function(e){
    e.preventDefault();
    var url = $(this).attr('href');


    $.ajax({
        method: "POST",
        url: url,
        success: function(reponse){
            $('<div id="dialog"></div>').html(reponse.create_html).dialog();
            $('form[name="trick"]').data('url-ajax', url);
        }
    })
});

// Affichage de la pop-up de modification
$(document).on('click' , '.edit-trick' , function(e){
    e.preventDefault();
    var url = $(this).attr('href');

    $.ajax({
        method: "POST",
        url: url,
        success: function(reponse){
            $('<div id="dialog"></div>').html(reponse.edit_html).dialog();
            $('form[name="trick"]').data('url-ajax', url);
        }
    });

})

// confirmation de la modification 
$(document).on('submit', 'form[name="trick"]', function(e) {
    e.preventDefault();
    // Récupération de l'URL
    var url = $(this).data('url-ajax');
    // Création de l'objet FormData pour récupérer toutes les données du formulaire, y compris les fichiers
    var formData = new FormData();

    // ! Impossible de récupéré l'image qui est checked
    // let check_box = $('.radio-file:checked');
    // let parent_checkedBox = $(check_box).parent();
    // var reader = new FileReader();
    // console.log(reader.readAsDataURL($('#trick_files').val())); 
    // console.log($('#trick_files').val()); 
    // $('.file-select').each(function() {
    //     if($('.file-select').parent()[0] == parent_checkedBox[0] ){
    //         console.log($(this)[0].src);
    //     }
    // });
   
    // console.log($(parent_checkedBox).children('img'));
    // let img = $(parent_checkedBox);
    
    // Ajouter manuellement les autres champs du formulaire
    $(this).find('input, textarea, select').not('input[type="file"]').each(function() {
        if ($(this).attr('name') !== "trick[links]") {
            formData.append($(this).attr('name'), $(this).val());
        }
    });

    var links = [];
    $('.parent-links input').each(function() {
        links.push($(this).val());
    });

    for (let i = 0; i < links.length; i++) {
        if (links[i] !== "" && links[i] !== null ) {
            formData.append('trick[links][]', links[i]);
        }
    };
    
    // Ajouter manuellement les fichiers sélectionnés dans FormData
    for (let i = 0; i < selectedFiles.length; i++) {
        formData.append('trick[files][]', selectedFiles[i]);
    }

    var existingFiles = [];
    $('#image-preview-existing img').each(function() {
        existingFiles.push($(this).data('filename'));
    });


    // Ajouter les fichiers existants au formData 
    formData.append('existing_files', JSON.stringify(existingFiles));
    formData.append('deleted_files', JSON.stringify(deletedFiles));
    
    $.ajax({
        method: "POST",
        url: url,
        data: formData, 
        processData: false,  
        contentType: false,  
        success: function(data) {
            selectedFiles = [];     
            $( "#dialog" ).dialog("close");

            if (data.page === "tricks") {
                // Mise à jour de la liste des tricks
                $('.tricks').html(data.tricks_html);
            } else if (data.page === "trick") {
                // Mise à jour du contenu du trick spécifique
                $('.content-show-trick').html(data.tricks_html);
            }
        },
        error: function(xhr) {
            if (xhr.status === 400) {
                const errors = xhr.responseJSON.errors; // Récupérer les erreurs du JSON
                console.log('Erreurs:', errors);

                // Afficher les erreurs sur l'interface utilisateur
                alert("Le formulaire contient des erreurs : \n" + errors.join("\n"));
            } else {
                console.error('Erreur lors de la soumission du formulaire:', xhr.responseText);
            }
        }
    });
});

// Afficher la pop-up de confirmation
$(document).on('click' , '.delete-pop-up' , function(e){
    e.preventDefault();
    var url = $(this).attr('href');
    var url_pathname = window.location.pathname;
    var url_split = url_pathname.split("/");
    var id_trick = url_split[2];
    console.log(id_trick);
    $.ajax({
        method: "POST",
        url: url,
        data : {id_trick},
        success: function(reponse){
            console.log(reponse)
            $('<div id="dialog-confirm"></div>').html(reponse.confirm_delete).dialog();
        }
    });
})

// Afficher la pop-up de confirmation
$(document).on('click' , '.confirm-delete' , function(e){
    e.preventDefault();
    // Récupéré l'url
    var url = $(this).data('url');
    // Récupéré le token
    var data = {
        _token : $('input[name="_token"').val(),
        id_trick : $(this).data('trick'),
    }
    // Requête
    $.ajax({
        method: "POST",
        url: url,
        data : data,
        success: function(reponse){
            $( "#dialog-confirm" ).dialog("destroy");
            // Affichage du retour
            if(reponse.redirection){
                location.href = reponse.url_redirect
            }else if(reponse.is_comment){
                $('.comments-container').html(reponse.comments_html);
                $('#comment_content').empty();
            }else{
                $('.tricks').html(reponse.tricks_html)

            }
        }
    });
})

// Submit du formulaire de commentaire
$(document).on('submit' , 'form[name="comment"]', function(e) {
    e.preventDefault();
    // Récupération de l'url 
    var url = $(this).children(".submit-comment").data("url");

    var tricks_value = new Object;
    // Formatage des données du formulaire
    var x = $(this).serializeArray(); 
    $.each(x, function(i, field) { 
        tricks_value[field.name] = field.value;
        
    });
    $.ajax({
        method: "POST",
        data : tricks_value,
        url: url,
        success: function(data){
            $('.comments-container').html(data.comments_html);
            $('#comment_content').empty();
        }
    })
})

$(document).on('click' , '.modify-comment' , function () {
    var id_comment = $(this).data("comment");
    var url = $(this).data("url");
    $.ajax({
        type:"POST",
        data: {
            id_comment : id_comment,
        },
        url: url,
        success: function(data){
            $('.comments-container').html(data.edit_html);
         }
    })

})

// Changement de la photo de profile
$(document).on('change' , '#profile-picture-form' , function(){
    var formData = new FormData();
    formData.append('file', $('#file-upload')[0].files[0]);
    $.ajax({
        url :  '/ajax/profile/avatar',
        type : 'POST',
        data : formData,
        processData: false,  // tell jQuery not to process the data
        contentType: false,  // tell jQuery not to set contentType
        success : function(response){
            $('.avatar-file-parent').html(response.html_avatar);
        }
    })
})

// Afficher les images séléctionner lors de la créeation de l'article
$(document).on('change' , '#trick_files' , function(event){
    // Récupérer les fichiers sélectionnés
    let files = event.target.files;
    // Boucler sur les fichiers sélectionnés et les ajouter au tableau selectedFiles
    for (let i = 0; i < files.length; i++) {
     selectedFiles.push(files[i]);
    }
    var preview = $('#image-preview');
    var div_preview = $('<div class="preview"></div>')
    console.log(files)
    for (let i = 0; i < files.length; i++) {
        var file = files[i];
        var reader = new FileReader();

        reader.onload = function(e) {
            let img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100px';
            img.style.margin = '5px';
            img.classList.add('file-select');
            $(preview).append(div_preview);
            $(div_preview).append(img);
            $(div_preview).append('<label for="first-file">Définir comme première image</label><input type="radio" name="first-file" class="radio-file">')
        }
        console.log(file);
        // Convertir le fichier en base64
        reader.readAsDataURL(file); 
    }
})

$(document).on('click' , '.close-file' , function(){
    let parent_this = $(this).parent();
    let file_delete = parent_this.children('.file-upload').data('filename');
    let img_element = parent_this.children('img');
    deletedFiles.push(file_delete);
    img_element.remove()
})

$(document).on('click' , '.add-links' , function(e){
    e.preventDefault();
    let link_wrap = $('<div class="link-wrap"><span class="close-link">&times;</span>')
    let input_text = $('<input type="text" name="trick[links]" inputmode="url">');
    link_wrap.append(input_text)
    $('.parent-links').append(link_wrap);
})

$(document).on('click', '.close-link' ,function(e){
    let parent_this = $(this).parent();
    parent_this.remove();
})

$(document).on('click' , '.btn-redirect-tricks' , function(e){
    e.preventDefault();
    var length_show = $('.trick-resume').length;
    var data = {
        trick_count : length_show
    }
    if (length_show >= 6){
        $.ajax({
            url :  $(this).attr("href"),
            type : 'POST',
            data : data,
            success : function(response){
                $('.show-more-tricks').html(response.html_tricks);
            }
        })
    }
})

$(document).on('click' , '.btn-show-more-comments' , function(e){
    e.preventDefault();
    var length_show = $('.content-comment').length;
    var url_pathname = window.location.pathname;
    var url_split = url_pathname.split("/");
    var id_trick = url_split[2];
    var data = {
        comment_count : length_show,
        id_trick : id_trick
    }
    if(length_show >= 6){
        $.ajax({
            url :  $(this).attr("href"),
            type : 'POST',
            data : data,
            success : function(response){
                $('.show-more-comments').html(response.html_comments);
            }
        })
    }
})