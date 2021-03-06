
require(['jquery'], function ($) {

    window.MegaventoryManager={
    
        changeCountsInStock : function (inventoryId , oneOrZero, url) {
    
            $.ajax({
                url: url,
                type: 'POST',
                showLoader: true,
                data: {
                    inventoryId:inventoryId,
                    value:oneOrZero
                },
                dataType: 'json',
                success: function (data) {
                    location.reload(true);
                },
                error: function (error) {
                    alert('An error occurred while saving the data.');
                }
            });
            
            /*new Ajax.Request(url, {

                method:'post',

                parameters : {

                    inventoryId:inventoryId,

                    value:oneOrZero

                },

                onFailure: function() {

                    alert('An error occurred while saving the data.');

                },

                onSuccess : function(response){

                }



            });*/
    
        },
    
        redo : function (url, logid) {
    
            $.ajax({
                url: url,
                type: 'POST',
                showLoader: true,
                data: {
                    logId:logid
                },
                dataType: 'json',
                success: function (data) {
                    location.reload(true);
                },
                error: function (error) {
                    alert('An error occurred while saving the data.');
                }
            });
            
        /*new Ajax.Request(url, {

            method:'post',

            parameters : {
                logId:logid
            },
            onFailure: function() {
                alert('An error occurred while saving the data.');
            },

            onSuccess : function(response){
                location.reload(true);
                }
            });*/
    
        },
        
        synchronizeOrder : function (url, orderid) {
            $.ajax({
                url: url,
                type: 'POST',
                showLoader: true,
                data: {
                    orderId:orderid,
                },
                dataType: 'json',
                success: function (data) {
                    location.reload(true);
                },
                error: function (error) {
                    alert('An error occurred while synchronizing order.');
                }
            });
            
            /*new Ajax.Request(url, {

                method:'post',

                parameters : {
                    orderId:orderid
                },
                onFailure: function() {
                    alert('An error occurred while synchronizing order.');
                },

                onSuccess : function(response){
                    location.reload(true);
                    }
                });*/
    
        },
        
        undeleteEntity : function (url, mvId, mvEntityType) {
    
            $.ajax({
                url: url,
                type: 'POST',
                showLoader: true,
                data: {
                    mvId: mvId,
                    mvEntityType : mvEntityType
                },
                dataType: 'json',
                success: function (data) {
                    alert('Entity was undeleted successfully!Please press save again to update entity in Megaventory.');
                },
                error: function (error) {
                    alert('An error occurred while undeleting entity.');
                }
            });
            
            
            /*new Ajax.Request(url, {

                method:'post',

                parameters : {
                    mvId: mvId,
                    mvEntityType : mvEntityType
                },
                onFailure: function() {
                    alert('An error occurred while undeleting entity.');
                },

                onSuccess : function(response){
                    alert('Entity was undeleted successfully!Please press save again to update entity in Megaventory.');
                    }
                });*/
    
        }
    }
});
