(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.pdf = {
    attach: function(context, settings) {
      PDFJS.workerSrc = settings.pdf.workerSrc;

      var canvases = context.getElementsByClassName("pdf-thumbnail");
      Array.prototype.forEach.call(canvases, function(canvas) {
        var file = canvas.attributes.file.value;
        PDFJS.getDocument(file).then(function(pdf) {
          pdf.getPage(1).then(function(page) {
            var scale = (canvas.attributes.scale) ? canvas.attributes.scale.value : 1;
            var viewport = page.getViewport(scale);
            var context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            var renderContext = {
              canvasContext: context,
              viewport: viewport
            };
            page.render(renderContext);
          });
        });
      });

      var fields = context.getElementsByClassName("pdf-pages");
      Array.prototype.forEach.call(fields, function(container) {
        var file = container.attributes.file.value;
        PDFJS.getDocument(file).then(function(pdf) {
          for (var i = 1; i <= pdf.numPages; i++) {
            pdf.getPage(i).then(function(page) {
              var canvas = document.createElement("canvas");
              canvas.setAttribute("class", "pdf-canvas");
              container.appendChild(canvas);
              var scale = (container.attributes.scale) ? container.attributes.scale.value : 1;
              var viewport = page.getViewport(scale);
              var context = canvas.getContext('2d');
              canvas.height = viewport.height;
              canvas.width = viewport.width;
              var renderContext = {
                canvasContext: context,
                viewport: viewport
              };
              page.render(renderContext);
            });
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
