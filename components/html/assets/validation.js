var validation = (function() {
	return {
        msgContainerId : 'errors_list',
		checkRules : {
			minlength3 : {
				rule : '[а-яёА-ЯЁa-zA-Z0-9_-]{3,}',
				msg : 'должно быть не менее 3 символов',
			},
			minlength6 : {
				rule : '[а-яёА-ЯЁa-zA-Z0-9_-]{6,}',
				msg : 'должно быть не менее 6 символов',
			},
			password : {
				rule : '^[a-z0-9]+$|i',
				msg : 'содержит недопустимые символы',
			},
            alpha : {
                rule : '^[а-яёa-z]+$|i',
                msg : '— текстовое поле',
            },
			alphaExt : {
				rule : '^[а-яёa-z- ]+$|i',
				msg : '— текстовое поле',
			},
			numeric : {
				rule : '\d+',
				msg : '— числовое поле',
			},
			telephone : {
				rule : '(\d{3,5})\d{5,7}',
				msg : 'неправильно заполнено',
			},
			fax : {
				rule : '\d{5,7}',
				msg : 'неправильно заполнено',
			},
			mobile : {
				rule : '\d{10,11}',
				msg : 'неправильно заполнено',
			},
			email : {
				rule : '([a-z0-9_\.-]{1,20})@([a-z0-9\.-]{1,20}).([a-z]{2,4})|i',
				msg : 'неправильно заполнено',
			},
		},
        msgContainer : null,
		data : [],
		metadata : [],
		
		run : function (fields) {
            this.msgContainer = dom.findById(this.msgContainerId);
			this.msgContainer.innerHTML = '';
            this.setData(fields);
			var invalidFields = this.validateData();
			if (invalidFields.length>0) {
				this.showMessage(invalidFields, 'error');
				return false;
			}
			return true;
		},

		/**
         * проверяем поля на валидность
         */
		validateData : function () {
			var invalidFields = [];
			var field = {};
			var fieldData = '';
			var fieldMetadata = {};
			for (var dataKey in this.data) {
				fieldData = this.data[dataKey];
				fieldMetadata = this.metadata[dataKey];
				for (var checkItmKey in fieldMetadata.check) {
					var checkItm = fieldMetadata.check[checkItmKey];
					// обязательные для заполненнения поля
					if (checkItm=='required') {
                        if (
						    fieldMetadata.type=='checkbox' && fieldData==false 
						    || (
							    (fieldMetadata.type=='input' || 'text' || 'textarea') 
							    && (fieldData=='' || fieldData===undefined)
                            )
					    ) {
						    invalidFields.push({
							    field: fieldMetadata.name, 
							    message: 'поле "' + fieldMetadata.title + '" обязательно для заполнения.'
						    }); // TODO: закинуть сообщение в конфиг
                        }
					} else if (obj.isObject(checkItm)) {
						// сравнение полей на идентичность
                        if (compareField = checkItm.compare) {
							if (fieldData!==this.data[compareField]) {
								invalidFields.push({
									field: fieldMetadata.name, 
									message: 'поле "' + fieldMetadata.title + '" не совпадает с ' + 'полем "' + this.metadata[compareField]['title']
								});
							}
						}
					} else {
						// проверяем по остальным критериям
						for (var checkKey in this.checkRules) {
							var checkRule = this.checkRules[checkKey];
                            if (checkItm!==checkKey)
                                continue;
							this.checkData(dataKey, checkKey, checkItm, checkRule['rule'], checkRule['msg'], invalidFields);
						}
					}
				}
			}
			
			return invalidFields;
		},

		checkData : function (dataKey, checkKey, checkItm, rule, errMsg, invalidFields) {
            var ruleArr = rule.split('|');
			var pattern = ruleArr[0];
			var flags = ruleArr[1];
            var regExpr;
			if (flags)
				regExpr = new RegExp(pattern, flags);
			else
				regExpr = new RegExp(pattern);
			
			if (!regExpr.test(this.data[dataKey])) {
				var fieldMetadata = this.metadata[dataKey];
				invalidFields.push({
					field: dataKey,
					message: 'поле "' + fieldMetadata.title + '" ' + errMsg
				});
			}
		},

		setData : function (fields) {
			this.data = {};
			this.metadata = {};

			for (var key in fields) {
				var field = fields[key];
				var fieldName = field.name;
				this.metadata[fieldName] = field;
				var data = dom.val(fieldName);
				if (data!=='undefined')
					this.data[fieldName] = data;
			}
		},

		/**
         * отображение сообщения
         */
		showMessage : function(messageData, type) {
            var message = '';
            this.msgContainer = dom.findById(this.msgContainerId);
            if (this.msgContainer===null) {
                if (type=='error') 
                    message += 'Внимание! ';

                if (obj.isArray(messageData)) {
                    for (var i=0; i<messageData.length; i++)
                        message += messageData[i].message + '; ';
                } else
                    message+=messageData;
                
                alert(message);
                return;
            }			
            
			if (type=='error') 
				message += '<b>Внимание</b>';

			if (obj.isArray(messageData)===true) {
				message += '<ul>';
				for (var i=0; i<messageData.length; i++)
					message += '<li>' + messageData[i].message + '</li>';

				message+='</ul>';
            } else if (obj.isObject(messageData)===true) {
                message += '<ul>';
                for (var key in messageData)
                    message += '<li>' + key + ": " + messageData[key] + '</li>';
                    
                message+='</ul>';
			} else
				message+=messageData;

			this.msgContainer.className = type;
			this.msgContainer.innerHTML = '<div class="' + type + '">' + message + '</div>';
		},
	};
})();
