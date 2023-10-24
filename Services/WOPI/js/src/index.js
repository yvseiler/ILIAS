import document from 'document';
import il from 'ilias';

il.WOPI = {
  modified: false,
};

il.WOPI.bindCloseButton = function (elementId) {
  const button = document.getElementById(elementId);
  button.addEventListener('click', (event) => {
    event.stopPropagation();
    event.preventDefault();
    this.close(() => {
      alert('saved');
    });
  });
};
il.WOPI.init = function () {
  // BUILD IFRAME
  const frameholder = document.getElementById('c-embedded-wopi');

  // read ttl, token and editor URL from data attributes
  const token = frameholder.getAttribute('data-token');
  const editorUrl = frameholder.getAttribute('data-editor-url');
  const ttl = frameholder.getAttribute('data-ttl');

  const editorFrame = document.createElement('iframe');
  editorFrame.name = 'editor_frame';
  editorFrame.id = 'editor_frame';
  editorFrame.title = 'Office Frame';
  editorFrame.setAttribute('allowfullscreen', 'true');
  editorFrame.setAttribute('allowtransparency', 'true');
  editorFrame.setAttribute('frameBorder', '0');
  frameholder.appendChild(editorFrame);

  this.frameholder = frameholder;
  this.editorFrame = editorFrame;
  // eslint-disable-next-line max-len
  this.editorFrameWindow = editorFrame.contentWindow || (editorFrame.contentDocument.document || editorFrame.contentDocument);
  this.windowResize();

  // BUILD FORM
  const form = document.createElement('form');
  const tokenInput = document.createElement('input');
  const ttlInput = document.createElement('input');

  form.method = 'POST';
  form.action = editorUrl;
  form.target = 'editor_frame';

  tokenInput.name = 'access_token';
  tokenInput.value = token;
  form.appendChild(tokenInput);

  ttlInput.name = 'access_token_ttl';
  ttlInput.value = ttl;
  form.appendChild(ttlInput);

  document.body.appendChild(form);

  // SEND FORM
  form.submit();
  // Add event listener to receive messages from the editor
  this.registerListener('App_LoadingStatus', (message) => {
    if (message.Values.Status === 'Document_Loaded') {
      this.postMessage({
        MessageId: 'Host_PostmessageReady',
        SendTime: Date.now(),
        Values: {},
      });
    }
  });

  this.registerListener('Doc_ModifiedStatus', (message) => {
    console.log('Documend Modified');
    this.modified = message.Values.Modified ?? false;
  });
  // Add event listener to resize the editor iframe
  document.defaultView.addEventListener('resize', () => {
    il.WOPI.windowResize(editorFrame);
  });
};

il.WOPI.windowResize = function () {
  const iframeWidth = this.editorFrame.parentElement.offsetWidth - 0;
  const iframeHeight = document.getElementsByClassName('il-layout-page-content')[0].clientHeight - document.getElementsByClassName('il_HeaderInner')[0].clientHeight - document.getElementsByTagName('footer')[0].clientHeight - 100;

  this.editorFrame.setAttribute('width', iframeWidth);
  this.editorFrame.setAttribute('height', iframeHeight);
};
il.WOPI.postMessage = function (mobj) {
  this.editorFrameWindow.postMessage(JSON.stringify(mobj), '*');
};
il.WOPI.registerListener = async function (MessageId, callback) {
  window.addEventListener(
    'message',
    (event) => {
      const message = JSON.parse(event.data);
      if (MessageId !== null && message.MessageId === MessageId) {
        callback(message);
      }
    },
    false,
  );
};
il.WOPI.close = async function (callback) {
  console.log('save called');

  const overlay = document.createElement('div');
  overlay.id = 'c-embedded-wopi-overlay';
  overlay.style.position = 'fixed';
  overlay.style.top = '0';
  overlay.style.left = '0';
  overlay.style.width = '100%';
  overlay.style.height = '100%';
  overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
  this.frameholder.appendChild(overlay);

  if (this.modified) {
    console.log('is modified');
    console.log('save');
    await this.save();
    await this.registerListener('Doc_ModifiedStatus', callback);
  }
};
il.WOPI.save = async function () {
  this.postMessage({
    MessageId: 'Action_Save',
    SendTime: Date.now(),
    Values: {
      DontTerminateEdit: true,
      DontSaveIfUnmodified: true,
      Notify: false,
    },
  });

  this.registerListener('App_Close', () => {



  return true;
};
