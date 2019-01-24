import $ from "jquery";
import ProgressBar from "progressbar.js";

class WCEC_Admin_Js {

    constructor() {

        this.$cmd = $('#cmd_text');

        this.bar = new ProgressBar.Line('#progress', {
            easing: 'easeInOut',
            color: '#4ebd4a',
        });

        this.ajaxData = {
            action: 'wcec_export',
            nonce: WCEC_Admin_Data.export_nonce,
            export_id: null,
            total_num_results: 0,
            step: 1,
        };

        $( document ).on('click', '#start-process', this.startExport.bind(this) );
        $( document ).on('click', '.stop-process', this.stopExport.bind(this) );
    }

    startExport() {
    	
    	if(this.processing){
    		alert('Already processing..');
    		return;
    	}

        this.$cmd.append('<span class="process">Processing..</span>');

        this.ajaxData.export_id = $('input[name="export_id"]:checked').val();
        this.ajaxData.step = 1;

    	this.processing = true;

    	// console.log(this.ajaxData);

    	this.doExport();
    }

    stopExport() {
        alert('stop');
    }

    doExport() {

    	// alert('do ajax');
        
        console.log('ajaxData', this.ajaxData);

    	$.ajax({
			dataType: 'json',
			data: this.ajaxData,
			type: 'post',
			url: WCEC_Admin_Data.ajax_url,
		}).then((response ) => {

            console.log('response', response);

            if( response.progress ){
                this.bar.animate( parseFloat( response.progress ) / 100 );  // Value from 0.0 to 1.0
            }

            if( response.messages ){
                this.updateConsole( response.messages );
            }

            if( response.current_step < response.total_steps ){

                this.ajaxData.step++;
                if( response.total_num_results ){
                    this.ajaxData.total_num_results = response.total_num_results;
                }

                this.doExport();
            } else {
                this.processing = false;

                if(response.download_url){
                    window.location = response.download_url;
                }
            }
			
		}, ( jqXHR, textStatus, errorThrown ) => {
			alert('fail');
			console.log(textStatus);
			this.processing = false;
		});
    }

    updateConsole(messages) {

        /**
         * Success
         * @param  {String} (typeof(messages.success) !             [description]
         * @return {[type]}                           [description]
         */
        if ( (typeof(messages.success) != 'undefined') && messages.success.length ) {
            for (let i = 0; i < messages.success.length; i++) { 
                this.$cmd.append('<span class="result success">'+messages.success[i]+'</span>');
            }
        }

        /**
         * Notices
         * @param  {String} (typeof(messages.notices) !             [description]
         * @return {[type]}                           [description]
         */
        if ( (typeof(messages.notices) != 'undefined') && messages.notices.length ) {
            for (let i = 0; i < messages.notices.length; i++) { 
                this.$cmd.append('<span class="result">'+messages.notices[i]+'</span>');
            }
        }

        /**
         * Error
         * @param  {String} (typeof(messages.errors) !             [description]
         * @return {[type]}                           [description]
         */
        if ( (typeof(messages.errors) != 'undefined') && messages.errors.length ) {
            for (let i = 0; i < messages.errors.length; i++) { 
                this.$cmd.append('<span class="result failed">'+messages.errors[i]+'</span>');
            }
        }

        $('#cmd').scrollTop( this.$cmd.get(0).scrollHeight );
    }
}

new WCEC_Admin_Js();