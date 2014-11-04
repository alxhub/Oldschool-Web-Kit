$( document ).ready(function(){
    $('form.ribbons .planet').fadeOut(0);
    $('form.ribbons #ribbons_submit').prop('disabled',true);
    $('form.ribbons #ribbons_submit').prop('value','Click a ribbon, then choose your options.');
    $('form.ribbons').not('.generate').submit(function(event){ // Validate form.
        isEmpty = true;
        inputs = $(this).find($('input'));
        inputs.each(function(index){
            thisElem = $(this);
            if(
                ! thisElem.prop('checked')
                || thisElem.attr('value') === 'None'
            ){ return; }
            isEmpty = false;
        });
//        if( isEmpty ){ alert('The form is empty!'); return false; }
    });
    $('.ribbons_output .cell').click(function(event){ // Ribbon clicked.
        event.preventDefault();
        planetClass = $(this).attr('title');
        if( ! planetClass ){ return; }
        planetClass = planetClass.replace(/\s+/ig,'_');
        if( planetClass ){
            $('form.ribbons .planet').fadeOut(0);
            $('form.ribbons .planet.'+planetClass).fadeIn('slow');
        }
    });
    $('form.ribbons .input_box input').change(function( event ){
        myRibbons.update(this,event);
    });
    
    myRibbons = new Object();
    myRibbons.update = function(target,event){
        var target = $(target);
        var parent = target.parent();
        var targetClass = parent.find($('label')).html().replace(/\s+/ig,'_');
        var input = parent.find($('input'));
        if( ! input.length ){
            input = parent.find($('select'));
        }
        var type = input.attr('type');
        var planetName = parent.attr('name');
        var planetVisitedInput = parent.find($('.visited')).find($('input'));
        var planetOrbitInput = parent.find($('.device.Orbit')).find($('input'));
        var ribbon = $('.ribbons_output .cell[title="'+planetName+'"]');
        var effectLayers = $('.ribbons_output .cell .effect.'+targetClass);
        checked = input.prop('checked');
        
        $('form.ribbons #ribbons_submit').prop('disabled',false);
        $('form.ribbons #ribbons_submit').prop('value','> > Ready! - Click here to save. < <');
        
        if( target.hasClass('effect') ){
            if( type === 'checkbox' ){
                if( checked ){
                    effectLayers.fadeTo('slow',1);
                }else{
                    effectLayers.fadeTo('slow',0);
                }
            }else if( type === 'radio' ){
                effectLayers.fadeTo('slow',1);
                $('.ribbons_output .cell .effect').not('.'+targetClass).not('.checkbox').fadeTo('slow',0);
            }
        }else if(
            target.hasClass('device')
            || target.hasClass('visited')
            || target.hasClass('asteroid')
        ){
            if(
                planetName === 'Asteroid'
                && $('#asteroid_none').prop('checked')
                && input.prop('value') !== 'None'
                && checked
            ){
                input.prop('checked',false);
                alert('You must choose an asteroid first.');
                return false;
            }
            if( planetName === 'Grand Tour' ){
                if(
                    targetClass === 'Grand_Tour:Landing'
                    || targetClass === 'Grand_Tour:Orbit'
                ){
                    value = input.prop('value');
                    if( value != '0' ){
                        planetVisitedInput.prop('checked',true);
                    }
                    if( targetClass === 'Grand_Tour:Landing' ){
                        allLayers = ribbon.find($('.device.gt_landing'));
                        i = 1; while( i <= allLayers.length ){
                            thisLayer = ribbon.find($('.device.Landing_'+i));
                            if( i <= value ){
                                thisLayer.fadeTo('slow',1);
                                thisLayer.addClass('selected');
                            }else{
                                thisLayer.fadeTo('slow',0);
                                thisLayer.removeClass('selected');
                            }
                            i++;
                        }
                    }
                    if( targetClass === 'Grand_Tour:Orbit' ){
                        allLayers = ribbon.find($('.device.gt_orbit'));
                        i = 1; while( i <= allLayers.length ){
                            thisLayer = ribbon.find($('.device.Orbit_'+i));
                            if( i <= value ){
                                thisLayer.fadeTo('slow',1);
                                thisLayer.addClass('selected');
                            }else{
                                thisLayer.fadeTo('slow',0);
                                thisLayer.removeClass('selected');
                            }
                            i++;
                        }
                    }
                }
            }
            
            if( type === 'checkbox' ){
                deviceLayer = ribbon.find($('.device.'+targetClass));
                checked = target.find($('input')).prop('checked');
                if( checked ){
                    deviceLayer.fadeTo('slow',1);
                    deviceLayer.addClass('selected');
                    planetVisitedInput.prop('checked',true);
                    if( targetClass === 'Equatorial' || targetClass === 'Polar' || targetClass === 'Geosynchronous' ){
                        parent.find($('.device.Orbit')).find($('input')).prop('checked',true);
                        ribbon.find($('.device.Orbit')).fadeTo('slow',1);
                    }
                }else{
                    deviceLayer.removeClass('selected');
                    deviceLayer.fadeTo('slow',0);
                    if( targetClass === 'Orbit' ){
                        parent.find($('.device.Equatorial')).find($('input')).prop('checked',false);
                        theLayer = ribbon.find($('.device.Equatorial'));
                        theLayer.fadeTo('slow',0);
                        theLayer.removeClass('selected');
                        parent.find($('.device.Polar')).find($('input')).prop('checked',false);
                        theLayer = ribbon.find($('.device.Polar'));
                        theLayer.fadeTo('slow',0);
                        theLayer.removeClass('selected');
                        parent.find($('.device.Geosynchronous')).find($('input')).prop('checked',false);
                        theLayer = ribbon.find($('.device.Geosynchronous'));
                        theLayer.fadeTo('slow',0);
                        theLayer.removeClass('selected');
                    }
                    if( target.hasClass('visited') ){
                        parent.find($('.device')).find($('input')).prop('checked',false);
                        parent.find($('.device')).find($('input[value="None"]')).prop('checked',true);
                        parent.find($('.device')).find($('option[value!="0"]')).prop('selected',false);
                        parent.find($('.device')).find($('option[value="0"]')).prop('selected',true);
                        allLayers = ribbon.find($('.device'));
                        allLayers.fadeTo('slow',0);
                        allLayers.removeClass('selected');
                    }
                }
            }else if( type === 'radio' ){
                parent.find($('.device')).each(function(index){
                    input = $(this).find($('input'));
                    value = input.attr('value');
                    deviceLayer = ribbon.find($('.device.'+value));
                    if( deviceLayer.length && input.prop('checked') ){
                        deviceLayer.fadeTo('slow',1);
                        deviceLayer.addClass('selected');
                        planetVisitedInput.prop('checked',true);
                    }else{
                        deviceLayer.fadeTo('slow',0);
                        deviceLayer.removeClass('selected');
                    }
                });
            }
            if( target.hasClass('asteroid') ){
                input = target.find($('input'));
                value = input.prop('value');
                asteroidLayers = ribbon.find($('.asteroid'));
                asteroidLayer = ribbon.find($('.asteroid.'+value));
                asteroidLayers.fadeTo('slow',0);
                asteroidLayers.removeClass('selected');
                if( value !== 'None' ){
                    asteroidLayer.fadeTo('slow',1);
                    asteroidLayer.addClass('selected');
                }else{
                    allInputs = parent.find($('.device')).find($('input'));
                    allInputs.prop('checked',false);
                    parent.find($('.device')).find($('input[value="None"]')).prop('checked',true);
                    allLayers = ribbon.find($('.device'));
                    allLayers.fadeTo('slow',0);
                    allLayers.removeClass('selected');
                }
            }
            
            if(
                ribbon.find($('.selected').not('.effect')).length
                || planetVisitedInput.prop('checked')
            ){
                ribbon.fadeTo('slow',1);
                ribbon.find('.name').fadeTo('slow',0);
            }else{
                ribbon.fadeTo('slow',0.5);
                ribbon.find('.name').fadeTo('slow',1);
            }
        }
    };
    // END of document.ready
});
