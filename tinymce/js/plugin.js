jQuery(document).ready( function($){
  
  var InsertInvitationCode;
  
  InsertInvitationCode = {
    invitation_modal: $('#invitation_modal'),
    editor: null,
    textarea: null,
    
    init: function (){
      $('#invitation_code_form').on('click','li',function(){
        $(this).siblings('li').removeClass('select');
        $(this).addClass('select');
      });
      $('#invitation_code_form').on('click', 'a.page-numbers', function(e){
        event.preventDefault();
        href = $(this).attr("href");
        if(href != undefined){
          paged = InsertInvitationCode.getpaged(href);
          InsertInvitationCode.refresh(paged);
        }
      });
      
      $('#invitation_code_form').on('click', 'a#invitation_code_search_btn', function(e){
        event.preventDefault();
        InsertInvitationCode.refresh(1);
      });
      
      $('#invitatino_code_insert').on('click', function(e){
        event.preventDefault();
        InsertInvitationCode.insert();
      });
      
      $('#invitatino_code_cancel').on('click',function(e){
        event.preventDefault();
        InsertInvitationCode.close();
      });
      
      $('#invitation_modal .modal-backdrop').on('click', function(e){
        InsertInvitationCode.close();
      });
      
      
    },
    open: function ( editorId ) {
      if ( editorId ) {
				window.wpActiveEditor = editorId;
			}

			if ( ! window.wpActiveEditor ) {
				return;
			}
      
      this.textarea = $( '#' + window.wpActiveEditor ).get( 0 );
      
      if ( typeof tinymce !== 'undefined' ) {
				ed = tinymce.get( wpActiveEditor );

				if ( ed && ! ed.isHidden() ) {
					this.editor = ed;
        }
      }
      
      this.textarea.focus();
      this.invitation_modal.show();
      $('body').addClass('modal-open');
    },
    close: function () {
      this.invitation_modal.hide();
      $('body').removeClass('modal-open');
      this.editor.focus();
    },
    insert: function () {
      code = $('#sinvitation_code_results').find('li.select .code_value').val();
      this.close();
			this.editor.focus();
      this.editor.insertContent( code );
    },
    getpaged:function (url) {
      var reg = new RegExp("(^|&)paged=([^&]*)(&|$)");
      var r = url.substr(1).match(reg);
      if (r != null){
        return r[2];
      }
      return r;
    },
    refresh: function ( paged ) {
      data = $('#invitation_code_form').serialize();
      data += '&paged='+paged;
      $.ajax( {
        url: ajaxurl,
        type: "POST",
        data: data,
        error: function(request) {
          
        },
        success: function(data) {
          result = $(data).find('#sinvitation_code_results');
          
          if(result.length>0){
            $('#invitation_code_form').html(data);
          }
          
        }
      });
    }
  }

  InsertInvitationCode.init();
  
  tinymce.PluginManager.add( 'InsertInvitationCode', function( editor, url ) {

		// Register a command so that it can be invoked by using tinyMCE.activeEditor.execCommand( 'WP_InsertPages' );
		editor.addCommand( 'InsertInvitationCode', function() {
			
      InsertInvitationCode.open();
      window.wpActiveEditor = editor.id;
			
		});
    
    editor.addButton( 'InsertInvitationCode', {
      title : 'Insert Invitation Code',
      icon : 'icon dashicons-admin-network',
			cmd: 'InsertInvitationCode',
      
		});

	});
});