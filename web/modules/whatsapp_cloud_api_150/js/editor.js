(function (Drupal, once) {
  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.inclusixpack_theme = {
    attach: function (context, settings) {
      once('start_Editor', '#drawflow').forEach(function () {
        startDrawFlow();
        startModal();
        startEditModal();
      })

    }
  };

  function startDrawFlow() {
    const container = document.getElementById('drawflow');
    window.editor = new Drawflow(container);

    editor.reroute = true;
    editor.reroute_fix_curvature = true;
    editor.start();

    const data = {
      name: ''
    };

    editor.addNode('start', 0, 1, 100, 200, 'start', { identification: 'start', type: 'start' }, 'Inicio');
    editor.addNode('end', 1, 0, 1000, 200, 'end', { identification: 'end', type: 'end' }, 'Fin');

    if (drupalSettings.whatsapp.dataToImport) {
      editor.clear();
      editor.import(drupalSettings.whatsapp.dataToImport);
    }
    editor.zoom = 0.4;
    editor.zoom_value = 0.05;
    editor.zoom_min = 0.1;
    editor.zoom_refresh();
    window.dataElements = {
      'start': {},
      'end': {},
    };
    if (drupalSettings.whatsapp.dataElements) {
      window.dataElements = drupalSettings.whatsapp.dataElements;
    }

    editor.on('click', function (event) {
      window.lastClickData = event;
      if (event.altKey == true) {
        showModal();
      }
    });

    editor.on('nodeSelected', id => {
      const nodeData = editor.getNodeFromId(id);
      window.lastClickElementName = nodeData.name;
      // Check if alt key is pressed
      if (window.lastClickData.altKey == true) {
        closeModal();
        showEditModal();
        putDataToEditModal(nodeData.data);
      }
    });

    editor.on('nodeRemoved', function (id) {
      const name = window.lastClickElementName;
      if (dataElements[name]) {
        delete dataElements[name];
      }
    });
  }

  function putDataToEditModal(data) {
    window.lastEditData = data;
    const fieldEditData = document.querySelector('#fieldEditData');
    if (fieldEditData) {
      const type = data.type;
      if (type == 'msg_type') {
        const fieldset = document.querySelector('#edit-message-type-msg');
        if (fieldset) {
          const fieldsetContent = fieldset.innerHTML;
          fieldEditData.innerHTML = fieldsetContent;

          const msgIdentification = fieldEditData.querySelector('#edit-msg-identification');
          const messageData = fieldEditData.querySelector('#edit-msg-message');
          const expectedResponse = fieldEditData.querySelector('#edit-msg-expected-response');
          const regularExpression = fieldEditData.querySelector('#edit-msg-regex');
          const uniqueData = fieldEditData.querySelector('#edit-msg-unique');
          const uniqueTable = fieldEditData.querySelector('#edit-msg-unique-table');
          const uniqueColumn = fieldEditData.querySelector('#edit-msg-unique-column');

          if (msgIdentification) {
            msgIdentification.value = data.identification;
          }
          if (messageData) {
            messageData.value = data.message;
          }
          console.log(data.expectedResponse)
          if (expectedResponse) {
            // crear un array dels tring separado por comas y hacer que cada option del select multiple tenga el atributo selected cuando corresponda y las demas sin el atributo
            const options = expectedResponse.options;
            const values = data.expectedResponse.split(',');
            Array.from(options).forEach(function (option) {
              if (values.includes(option.value)) {
                option.setAttribute('selected', true);
              }
              else {
                option.removeAttribute('selected');
              }
            });
          }
          if (regularExpression) {
            regularExpression.value = data.regex;
          }
          if (uniqueData) {
            uniqueData.checked = data.mustUnique;
          }
          if (uniqueTable) {
            uniqueTable.value = data.uniqueTable;
          }
          if (uniqueColumn) {
            uniqueColumn.value = data.uniqueColumn;
          }
        }

      }
      else if (type == 'reply_button') {
        const fieldset = document.querySelector('#edit-message-type-button');
        if (fieldset) {
          const fieldsetContent = fieldset.innerHTML;
          fieldEditData.innerHTML = fieldsetContent;

          fieldEditData.querySelector('#edit-button-identification').value = data.identification;
          fieldEditData.querySelector('#edit-button-message').value = data.message;
          data.btns.forEach((button, index) => {
            const btn_id = fieldEditData.querySelector('#edit-button-' + (index + 1) + '-id');
            const btn_msg = fieldEditData.querySelector('#edit-button-' + (index + 1) + '-msg');
            if (btn_id) {
              btn_id.value = button.btn_id;
            }
            if (btn_msg) {
              btn_msg.value = button.btn_msg;
            }
          });
        }

      }
      else if (type == 'list_type') {
        const fieldset = document.querySelector('#edit-message-type-list');
        if (fieldset) {
          const fieldsetContent = fieldset.innerHTML;
          fieldEditData.innerHTML = fieldsetContent;

          fieldEditData.querySelector('#edit-list-identification').value = data.identification;
          fieldEditData.querySelector('#edit-list-message').value = data.message;
          fieldEditData.querySelector('#edit-list-open-message').value = data.lostOpenButton;

          data.options.forEach((list, index) => {
            const list_id = fieldEditData.querySelector('#edit-list-' + (index + 1) + '-id');
            const list_msg = fieldEditData.querySelector('#edit-list-' + (index + 1) + '-msg');
            const list_desc = fieldEditData.querySelector('#edit-list-' + (index + 1) + '-desc');
            if (list_id) {
              list_id.value = list.id;
            }
            if (list_msg) {
              list_msg.value = list.msg;
            }
            if (list_desc) {
              list_desc.value = list.desc;
            }
          });
        }
      }
    }
    else {
      closeEditModal();
    }
  }

  let modal;
  function startModal() {
    const span = document.querySelector('.closeModal');
    modal = document.querySelector('#saveModal');
    span.onclick = function () {
      closeModal()
    }

    window.addEventListener('click', function (event) {
      if (event.target == modal) {
        closeModal()
      }
    });
    const type_msg = document.querySelector('#msg_type');
    if (type_msg) {
      type_msg.addEventListener('click', function (e) {
        e.preventDefault();
        window.typeMessage = 'msg_type';
        const fieldset = document.querySelector('#edit-message-type-msg');
        if (fieldset) {
          const fieldSetData = document.querySelector('#fieldSetData');
          if (fieldSetData) {
            const fieldsetContent = fieldset.innerHTML;
            fieldSetData.innerHTML = fieldsetContent;
          }
        }
      });
    }

    const type_reply_button = document.querySelector('#reply_button_type');
    if (type_reply_button) {
      type_reply_button.addEventListener('click', function (e) {
        e.preventDefault();
        window.typeMessage = 'reply_button';
        const fieldset = document.querySelector('#edit-message-type-button');
        if (fieldset) {
          const fieldSetData = document.querySelector('#fieldSetData');
          if (fieldSetData) {
            const fieldsetContent = fieldset.innerHTML;
            fieldSetData.innerHTML = fieldsetContent;
          }
        }
      });
    }

    const end_type = document.querySelector('#end_type');
    if (end_type) {
      end_type.addEventListener('click', function (e) {
        e.preventDefault();
        window.typeMessage = 'end_type';

        const fieldSetData = document.querySelector('#fieldSetData');
        if (fieldSetData) {
          const textElement = document.createElement('div');
          textElement.innerHTML = 'Se va a agregar un elemento de tipo fin';
          textElement.classList.add('form-item__description');
          fieldSetData.innerHTML = '';
          fieldSetData.append(textElement);
        }
      });
    }

    const agent_type = document.querySelector('#agent_type');
    if (agent_type) {
      agent_type.addEventListener('click', function (e) {
        e.preventDefault();
        window.typeMessage = 'agent_type';

        const fieldSetData = document.querySelector('#fieldSetData');
        if (fieldSetData) {
          const textElement = document.createElement('div');
          textElement.innerHTML = 'Se va a agregar un elemento de tipo "Hablar con un asesor"';
          textElement.classList.add('form-item__description');
          fieldSetData.innerHTML = '';
          fieldSetData.append(textElement);
        }
      });
    }

    const list_type = document.querySelector('#list_type');
    if (list_type) {
      list_type.addEventListener('click', function (e) {
        e.preventDefault();
        window.typeMessage = 'list_type';
        const fieldset = document.querySelector('#edit-message-type-list');
        if (fieldset) {
          const fieldSetData = document.querySelector('#fieldSetData');
          if (fieldSetData) {
            const fieldsetContent = fieldset.innerHTML;
            fieldSetData.innerHTML = fieldsetContent;
          }
        }
      });
    }

    // Save element
    const save_type = document.querySelector('#save_type');
    if (save_type) {
      save_type.addEventListener('click', e => {
        e.preventDefault();
        const fieldSetData = document.querySelector('#fieldSetData');

        const requiredError = document.createElement('div');
        requiredError.classList.add('form-item__description');
        requiredError.classList.add('error-message');
        requiredError.innerHTML = 'El campo es boligatorio';

        const duplicatedIdError = requiredError.cloneNode(true);
        duplicatedIdError.innerHTML = "La identificación ya existe";

        if (fieldSetData) {
          if (window.typeMessage == 'msg_type') {
            const msgIdentification = fieldSetData.querySelector('#edit-msg-identification');
            const messageData = fieldSetData.querySelector('#edit-msg-message');
            const expectedResponse = fieldSetData.querySelector('#edit-msg-expected-response');
            const regularExpression = fieldSetData.querySelector('#edit-msg-regex');
            const uniqueData = fieldSetData.querySelector('#edit-msg-unique');
            const uniqueTable = fieldSetData.querySelector('#edit-msg-unique-table');
            const uniqueColumn = fieldSetData.querySelector('#edit-msg-unique-column');

            const msgIdentificationData = msgIdentification?.value?.trim();
            const messageDataData = messageData?.value?.trim();
            const expectedResponseData = getSelectValues(expectedResponse).join(',');
            const regularExpressionData = regularExpression?.value?.trim();
            const uniqueDataData = uniqueData?.checked;
            const uniqueTableData = uniqueTable?.value?.trim();
            const uniqueColumnData = uniqueColumn?.value?.trim();

            let error = false;

            if (!msgIdentificationData) {
              msgIdentification.insertAdjacentElement('afterend', requiredError.cloneNode(true));
              error = true;
            }
            if (!messageDataData) {
              messageData.insertAdjacentElement('afterend', requiredError.cloneNode(true));
              error = true;
            }

            if (dataElements[msgIdentificationData] !== undefined) {
              msgIdentification.insertAdjacentElement('afterend', duplicatedIdError.cloneNode(true));
              error = true;
            }


            if (error) {
              return;
            }
            var data = {
              'type': window.typeMessage,
              "identification": msgIdentificationData,
              "message": messageDataData,
              "expectedResponse": expectedResponseData,
              "regex": regularExpressionData,
              "mustUnique": uniqueDataData,
              'uniqueTable': uniqueTableData,
              "uniqueColumn": uniqueColumnData,
            };
            dataElements[msgIdentificationData] = data;
            const newNodeId = editor.addNode(msgIdentificationData, 1, 1, getPosLastClickX(), getPosLastClickY(), 'msg_input_box', data, messageDataData.replace(/\n\r?/g, '<br />'));
            dataElements[msgIdentificationData].id = newNodeId;
            editor.drawflow.drawflow.Home.data[newNodeId].data.id = newNodeId;
          } else if (window.typeMessage == 'reply_button') {

            const btnIdentification = fieldSetData.querySelector('#edit-button-identification');
            const btnMessage = fieldSetData.querySelector('#edit-button-message');

            const btn1_id = fieldSetData.querySelector('#edit-button-1-id');
            const btn2_id = fieldSetData.querySelector('#edit-button-2-id');
            const btn3_id = fieldSetData.querySelector('#edit-button-3-id');

            const btn1_msg = fieldSetData.querySelector('#edit-button-1-msg');
            const btn2_msg = fieldSetData.querySelector('#edit-button-2-msg');
            const btn3_msg = fieldSetData.querySelector('#edit-button-3-msg');

            const btnIdentificationData = btnIdentification?.value?.trim();
            const btnMessageData = btnMessage?.value?.trim();

            const btn1_idData = btn1_id?.value?.trim();
            const btn2_idData = btn2_id?.value?.trim();
            const btn3_idData = btn3_id?.value?.trim();

            const btn1_msgData = btn1_msg?.value?.trim();
            const btn2_msgData = btn2_msg?.value?.trim();
            const btn3_msgData = btn3_msg?.value?.trim();

            let error = false;

            if (!btnIdentificationData) {
              btnIdentification.insertAdjacentElement('afterend', requiredError.cloneNode(true));
              error = true;
            }
            if (!btnMessageData) {
              btnMessage.insertAdjacentElement('afterend', requiredError.cloneNode(true));
              error = true;
            }
            if (dataElements[btnIdentificationData] !== undefined) {
              btnIdentification.insertAdjacentElement('afterend', duplicatedIdError.cloneNode(true));
              error = true;
            }


            if (error) {
              return;
            }
            var data = {
              'type': window.typeMessage,
              "identification": btnIdentificationData,
              "message": btnMessageData,
              "btns": [],
            };

            let btn1Html = '';
            let btn2Html = '';
            let btn3Html = '';
            let countBtns = 0;
            if (btn1_msgData && btn1_idData) {
              countBtns++;
              btn1Html = `<div><span>${btn1_msgData}</span></div>`;
              data.btns.push({
                btn_id: btn1_idData,
                btn_msg: btn1_msgData,
              });
            }
            if (btn2_msgData && btn2_idData) {
              countBtns++;
              btn2Html = `<div><span>${btn2_msgData}</span></div>`;
              data.btns.push({
                btn_id: btn2_idData,
                btn_msg: btn2_msgData,
              });
            }
            if (btn3_msgData && btn3_idData) {
              countBtns++;
              btn3Html = `<div><span>${btn3_msgData}</span></div>`;
              data.btns.push({
                btn_id: btn3_idData,
                btn_msg: btn3_msgData,
              });
            }

            dataElements[btnIdentificationData] = data;
            let boxHTML = `
            <div class="btn_reply_box">
              <div class="btn_reply_box_msg">${btnMessageData.replace(/\n\r?/g, '<br />')}</div>
              <div class="btn_reply_box_btns">
                ${btn1Html}
                ${btn2Html}
                ${btn3Html}
              </div>
            </div>
            `;
            const newNodeId = editor.addNode(btnIdentificationData, 1, countBtns, getPosLastClickX(), getPosLastClickY(), 'msg_input_box btn_info', data, boxHTML);
            dataElements[btnIdentificationData].id = newNodeId;
            editor.drawflow.drawflow.Home.data[newNodeId].data.id = newNodeId;
          } else if (window.typeMessage == 'end_type') {
            const data = {
              'type': 'end',
              "identification": 'end',
            };
            editor.addNode('end', 1, 0, getPosLastClickX(), getPosLastClickY(), 'end', data, 'Fin');
          } else if (window.typeMessage == 'agent_type') {
            const data = {
              'type': 'agent',
              "identification": 'agent',
            };
            dataElements['agent'] = data;
            editor.addNode('agent', 1, 0, getPosLastClickX(), getPosLastClickY(), 'agent', data, 'Asesor');
          } else if (window.typeMessage == 'list_type') {
            // Obtener los datos de los inputs referente a la lista.
            const listIdentification = fieldSetData.querySelector('#edit-list-identification');
            const listMessage = fieldSetData.querySelector('#edit-list-message');
            const lostOpenButton = fieldSetData.querySelector('#edit-list-open-message');

            const listIdentificationData = listIdentification?.value?.trim();
            const listMessageData = listMessage?.value?.trim();
            const lostOpenButtonData = lostOpenButton?.value?.trim();

            let error = false;

            if (!listIdentificationData) {
              listIdentification.insertAdjacentElement('afterend', requiredError.cloneNode(true));
              error = true;
            }
            if (!listMessageData) {
              listMessage.insertAdjacentElement('afterend', requiredError.cloneNode(true));
              error = true;
            }
            if (dataElements[listIdentificationData] !== undefined) {
              listIdentification.insertAdjacentElement('afterend', duplicatedIdError.cloneNode(true));
              error = true;
            }

            if (error) {
              return;
            }

            const listOptions = [];
            let htmlList = '';
            let countListOptions = 0;
            for (let i = 1; i <= 10; i++) {
              const listOptionId = fieldSetData.querySelector(`#edit-list-${i}-id`);
              const listOptionMessage = fieldSetData.querySelector(`#edit-list-${i}-msg`);
              const listOptionDescription = fieldSetData.querySelector(`#edit-list-${i}-desc`);
              if (listOptionId && listOptionMessage) {
                const listOptionIdData = listOptionId?.value?.trim();
                const listOptionMessageData = listOptionMessage?.value?.trim();
                const listOptionDescriptionData = listOptionDescription?.value?.trim() ?? '';
                if (listOptionIdData && listOptionMessageData) {
                  countListOptions++;
                  listOptions.push({
                    id: listOptionIdData,
                    msg: listOptionMessageData,
                    desc: listOptionDescriptionData,
                  });
                  htmlList += `<div class="list_option">${listOptionMessageData}</div>`;
                }
              }
            }

            const boxListHtml = `
                <div class="list_box">
                  <div class="list_box_msg">${listMessage.value.replace(/\n\r?/g, '<br />')}</div>
                  <div class="list_box_options">
                    ${htmlList}
                  </div>
                </div>
              `;

            const data = {
              'type': window.typeMessage,
              "identification": listIdentificationData,
              "message": listMessageData,
              "lostOpenButton": lostOpenButtonData,
              "options": listOptions,
            };

            dataElements[listIdentificationData] = data;
            const newNodeId = editor.addNode(listIdentificationData, 1, countListOptions, getPosLastClickX(), getPosLastClickY(), 'msg_input_box list_info', data, boxListHtml);
            dataElements[listIdentificationData].id = newNodeId;
            editor.drawflow.drawflow.Home.data[newNodeId].data.id = newNodeId;
          }
        }
        closeModal();
      });
    }
  }

  function getPosLastClickX() {
    let pos_x = window.lastClickData.clientX;
    pos_x = pos_x * (editor.precanvas.clientWidth / (editor.precanvas.clientWidth * editor.zoom)) - (editor.precanvas.getBoundingClientRect().x * (editor.precanvas.clientWidth / (editor.precanvas.clientWidth * editor.zoom)));

    return pos_x;
  }

  function getPosLastClickY() {
    let pos_y = window.lastClickData.clientY;

    pos_y = pos_y * (editor.precanvas.clientHeight / (editor.precanvas.clientHeight * editor.zoom)) - (editor.precanvas.getBoundingClientRect().y * (editor.precanvas.clientHeight / (editor.precanvas.clientHeight * editor.zoom)));

    return pos_y;
  }

  function closeModal() {
    modal.classList.remove('show');
  }

  function showModal() {
    modal.classList.add('show');
    const fieldSetData = document.querySelector('#fieldSetData');
    fieldSetData.innerHTML = '';
  }

  let editModal;
  function startEditModal() {
    const span = document.querySelector('.closeEditModal');
    editModal = document.querySelector('#editModal');
    span.onclick = function () {
      closeEditModal()
    }

    window.addEventListener('click', function (event) {
      if (event.target == editModal) {
        closeEditModal()
      }
    });

    //Update data
    const updateData = document.querySelector('#update_data');
    const fieldEditData = document.querySelector('#fieldEditData');
    if (updateData && fieldEditData) {

      const requiredError = document.createElement('div');
      requiredError.classList.add('form-item__description');
      requiredError.classList.add('error-message');
      requiredError.innerHTML = 'El campo es boligatorio';

      const duplicatedIdError = requiredError.cloneNode(true);
      duplicatedIdError.innerHTML = "La identificación ya existe";

      updateData.addEventListener('click', async e => {
        const editType = window.lastEditData.type;
        if (editType == 'msg_type') {
          const msgIdentification = fieldEditData.querySelector('#edit-msg-identification');
          const messageData = fieldEditData.querySelector('#edit-msg-message');
          const expectedResponse = fieldEditData.querySelector('#edit-msg-expected-response');
          const regularExpression = fieldEditData.querySelector('#edit-msg-regex');
          const uniqueData = fieldEditData.querySelector('#edit-msg-unique');
          const uniqueTable = fieldEditData.querySelector('#edit-msg-unique-table');
          const uniqueColumn = fieldEditData.querySelector('#edit-msg-unique-column');

          const msgIdentificationData = msgIdentification?.value?.trim();
          const messageDataData = messageData?.value?.trim();
          const expectedResponseData = expectedResponse?.value?.trim();
          const regularExpressionData = regularExpression?.value?.trim();
          const uniqueDataData = uniqueData?.checked;
          const uniqueTableData = uniqueTable?.value?.trim();
          const uniqueColumnData = uniqueColumn?.value?.trim();

          let error = false;

          if (!msgIdentificationData) {
            msgIdentification.insertAdjacentElement('afterend', requiredError.cloneNode(true));
            error = true;
          }
          if (!messageDataData) {
            messageData.insertAdjacentElement('afterend', requiredError.cloneNode(true));
            error = true;
          }
          if (dataElements[msgIdentificationData] !== undefined && msgIdentificationData !== window.lastEditData.identification) {
            msgIdentification.insertAdjacentElement('afterend', duplicatedIdError.cloneNode(true));
            error = true;
          }

          if (error) {
            return;
          }

          const elementNodeId = window.lastEditData.id;
          const data = {
            'type': editType,
            'id': elementNodeId,
            "identification": msgIdentificationData,
            "message": messageDataData,
            "expectedResponse": expectedResponseData,
            "regex": regularExpressionData,
            "mustUnique": uniqueDataData,
            "uniqueTable": uniqueTableData,
            "uniqueColumn": uniqueColumnData,
          };
          delete dataElements[window.lastEditData.identification];
          dataElements[msgIdentificationData] = data;
          editor.drawflow.drawflow.Home.data[elementNodeId].html = messageDataData.replace(/\n\r?/g, '<br />');
          editor.drawflow.drawflow.Home.data[elementNodeId].name = msgIdentificationData;
          editor.updateNodeDataFromId(elementNodeId, data);
          document.querySelector('#save_flow').click();
        } else if (editType == 'reply_button') {
          // Hacer lo mismo que se hizo arriba, pero para el tipo botón.
          const btnIdentification = fieldEditData.querySelector('#edit-button-identification');
          const btnMessage = fieldEditData.querySelector('#edit-button-message');

          const btn1_id = fieldEditData.querySelector('#edit-button-1-id');
          const btn2_id = fieldEditData.querySelector('#edit-button-2-id');
          const btn3_id = fieldEditData.querySelector('#edit-button-3-id');

          const btn1_msg = fieldEditData.querySelector('#edit-button-1-msg');
          const btn2_msg = fieldEditData.querySelector('#edit-button-2-msg');
          const btn3_msg = fieldEditData.querySelector('#edit-button-3-msg');

          const btnIdentificationData = btnIdentification?.value?.trim();
          const btnMessageData = btnMessage?.value?.trim();
          const btn1_idData = btn1_id?.value?.trim();
          const btn1_msgData = btn1_msg?.value?.trim();
          const btn2_idData = btn2_id?.value?.trim();
          const btn2_msgData = btn2_msg?.value?.trim();
          const btn3_idData = btn3_id?.value?.trim();
          const btn3_msgData = btn3_msg?.value?.trim();

          let error = false;

          if (!btnIdentificationData) {
            btnIdentification.insertAdjacentElement('afterend', requiredError.cloneNode(true));
            error = true;
          }
          if (!btnMessageData) {
            btnMessage.insertAdjacentElement('afterend', requiredError.cloneNode(true));
            error = true;
          }
          if (dataElements[btnIdentificationData] !== undefined && btnIdentificationData !== window.lastEditData.identification) {
            btnIdentification.insertAdjacentElement('afterend', duplicatedIdError.cloneNode(true));
            error = true;
          }

          if (error) {
            return;
          }

          const elementNodeId = window.lastEditData.id;
          const data = {
            'type': editType,
            'id': elementNodeId,
            "identification": btnIdentificationData,
            "message": btnMessageData,
            "btns": []
          };

          let btn1Html = '';
          let btn2Html = '';
          let btn3Html = '';
          if (btn1_idData && btn1_msgData) {
            btn1Html = `<div><span>${btn1_msgData}</span></div>`;
            data.btns.push({
              btn_id: btn1_idData,
              btn_msg: btn1_msgData,
            });
          }
          if (btn2_idData && btn2_msgData) {
            btn2Html = `<div><span>${btn2_msgData}</span></div>`;
            data.btns.push({
              btn_id: btn2_idData,
              btn_msg: btn2_msgData,
            });
          }
          if (btn3_idData && btn3_msgData) {
            btn3Html = `<div><span>${btn3_msgData}</span></div>`;
            data.btns.push({
              btn_id: btn3_idData,
              btn_msg: btn3_msgData,
            });
          }

          let boxHTML = `
            <div class="btn_reply_box">
              <div class="btn_reply_box_msg">${btnMessageData.replace(/\n\r?/g, '<br />')}</div>
              <div class="btn_reply_box_btns">
                ${btn1Html}
                ${btn2Html}
                ${btn3Html}
              </div>
            </div>
            `;

          delete dataElements[window.lastEditData.identification];
          console.log('btnIdentificationData', btnIdentificationData)
          dataElements[btnIdentificationData] = data;
          editor.drawflow.drawflow.Home.data[elementNodeId].html = boxHTML;
          editor.drawflow.drawflow.Home.data[elementNodeId].name = btnIdentificationData
          editor.updateNodeDataFromId(elementNodeId, data);

          const currentCountOuputs = Object.keys(editor.drawflow.drawflow.Home.data[elementNodeId].outputs).length;
          const newCountButtons = data.btns.length;
          // Si la cantidad de botones cambió, se debe agregar o eliminar los outputs.
          if (newCountButtons < currentCountOuputs) {
            // Si la cantidad de botones es menor a la cantidad de outputs, se deben eliminar los outputs sobrantes.
            for (let i = 0; i < currentCountOuputs - newCountButtons; i++) {
              editor.removeNodeOutput(elementNodeId, `output_${currentCountOuputs - i}`);
            }
          }
          else if (newCountButtons > currentCountOuputs) {
            // Si la cantidad de botones es mayor a la cantidad de outputs, se deben agregar los outputs faltantes.
            for (let i = 0; i < newCountButtons - currentCountOuputs; i++) {
              editor.addNodeOutput(elementNodeId);
            }
          }
          else {
            // Si la cantidad de botones es igual a la cantidad de outputs, no se hace nada.
          }
          document.querySelector('#save_flow').click();
        }
        else if (editType == 'list_type') {
          const listIdentification = fieldEditData.querySelector('#edit-list-identification');
          const listMessage = fieldEditData.querySelector('#edit-list-message');
          const listOpenMessage = fieldEditData.querySelector('#edit-list-open-message');

          const listIdentificationData = listIdentification?.value?.trim();
          const listMessageData = listMessage?.value?.trim();
          const listOpenMessageData = listOpenMessage?.value?.trim();

          let error = false;

          if (!listIdentificationData) {
            listIdentification.insertAdjacentElement('afterend', requiredError.cloneNode(true));
            error = true;
          }
          if (!listMessageData) {
            listMessage.insertAdjacentElement('afterend', requiredError.cloneNode(true));
            error = true;
          }
          if (!listOpenMessageData) {
            listOpenMessage.insertAdjacentElement('afterend', requiredError.cloneNode(true));
            error = true;
          }

          if (error) {
            return;
          }

          const listOptions = [];
          let htmlList = '';
          for (let i = 1; i <= 10; i++) {
            const listOptionId = fieldEditData.querySelector(`#edit-list-${i}-id`);
            const listOptionMsg = fieldEditData.querySelector(`#edit-list-${i}-msg`);
            const listOptionDesc = fieldEditData.querySelector(`#edit-list-${i}-desc`);
            const listOptionIdData = listOptionId?.value?.trim();
            const listOptionMsgData = listOptionMsg?.value?.trim();
            const listOptionDescData = listOptionDesc?.value?.trim();
            if (listOptionIdData && listOptionMsgData) {
              listOptions.push({
                id: listOptionIdData,
                msg: listOptionMsgData,
                desc: listOptionDescData,
              });
              htmlList += `<div class="list_option">${listOptionMsgData}</div>`;
            }
          }

          const boxListHtml = `
              <div class="list_box">
                <div class="list_box_msg">${listMessage.value.replace(/\n\r?/g, '<br />')}</div>
                <div class="list_box_options">
                  ${htmlList}
                </div>
              </div>
            `;

          const elementNodeId = window.lastEditData.id;
          const data = {
            'type': editType,
            'id': elementNodeId,
            "identification": listIdentificationData,
            "message": listMessageData,
            "lostOpenButton": listOpenMessageData,
            "options": listOptions,
          };

          delete dataElements[window.lastEditData.identification];
          dataElements[listIdentificationData] = data;
          editor.drawflow.drawflow.Home.data[elementNodeId].html = boxListHtml;
          editor.drawflow.drawflow.Home.data[elementNodeId].name = listIdentificationData
          editor.updateNodeDataFromId(elementNodeId, data);

          const currentCountOuputs = Object.keys(editor.drawflow.drawflow.Home.data[elementNodeId].outputs).length;
          const newCountOptions = data.options.length;
          // Si la cantidad de opciones cambió, se debe agregar o eliminar los outputs.
          if (newCountOptions < currentCountOuputs) {
            // Si la cantidad de opciones es menor a la cantidad de outputs, se deben eliminar los outputs sobrantes.
            for (let i = 0; i < currentCountOuputs - newCountOptions; i++) {
              editor.removeNodeOutput(elementNodeId, `output_${currentCountOuputs - i}`);
            }
          }
          else if (newCountOptions > currentCountOuputs) {
            // Si la cantidad de opciones es mayor a la cantidad de outputs, se deben agregar los outputs faltantes.
            for (let i = 0; i < newCountOptions - currentCountOuputs; i++) {
              editor.addNodeOutput(elementNodeId);
            }
          }
          else {
            // Si la cantidad de opciones es igual a la cantidad de outputs, no se hace nada.
          }
          document.querySelector('#save_flow').click();
        }
      });

    }
  }

  function closeEditModal() {
    editModal.classList.remove('show');
  }

  function showEditModal() {
    editModal.classList.add('show');
    const fieldSetData = document.querySelector('#fieldSetData');
    fieldSetData.innerHTML = '';
  }



  const saveFlow = document.querySelector('#save_flow');
  if (saveFlow) {
    saveFlow.addEventListener('click', async e => {
      e.preventDefault();
      const response = await fetch(`/whats150/save/flow`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: JSON.stringify({
          'dataElements': dataElements,
          'exportData': editor.export(),
        }),
      });
      const dataResponse = await response.json();
      if (response.status == 200) {
        location.reload();
      }
    })
  }

  function getSelectValues(select) {
    var result = [];
    var options = select && select.options;
    var opt;

    for (var i = 0, iLen = options.length; i < iLen; i++) {
      opt = options[i];

      if (opt.selected) {
        result.push(opt.value || opt.text);
      }
    }
    return result;
  }

}(Drupal, once));
