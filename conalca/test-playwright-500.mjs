import { chromium } from 'playwright';

(async () => {
  console.log('\n=================================================');
  console.log('VERIFICACIÓN ERROR 500 CON PLAYWRIGHT');
  console.log('=================================================\n');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
  });
  const page = await context.newPage();

  const url = process.argv[2] || 'http://localhost:8000';

  console.log(`→ Navegando a: ${url}\n`);

  try {
    // Capturar errores de consola
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log(`[BROWSER ERROR]: ${msg.text()}`);
      }
    });

    // Capturar errores de página
    page.on('pageerror', error => {
      console.log(`[PAGE ERROR]: ${error.message}`);
    });

    // Capturar respuestas HTTP
    page.on('response', response => {
      if (response.status() >= 400) {
        console.log(`[HTTP ${response.status()}]: ${response.url()}`);
      }
    });

    const response = await page.goto(url, {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    const status = response.status();
    console.log(`=================================================`);
    console.log(`Status Code: ${status}`);
    console.log(`Status Text: ${response.statusText()}`);
    console.log(`URL Final: ${page.url()}`);
    console.log(`=================================================\n`);

    if (status === 500) {
      console.log('✗ ERROR 500 DETECTADO\n');

      // Capturar screenshot
      await page.screenshot({ path: 'error-500-screenshot.png', fullPage: true });
      console.log('✓ Screenshot guardado en: error-500-screenshot.png\n');

      // Extraer título y contenido
      const title = await page.title();
      console.log(`Título de la página: ${title}\n`);

      // Intentar extraer mensaje de error
      const errorMessage = await page.evaluate(() => {
        const selectors = [
          '.message',
          '.exception-message',
          '.error-message',
          'h1',
          '.title'
        ];
        
        for (const selector of selectors) {
          const el = document.querySelector(selector);
          if (el) return el.textContent?.trim();
        }
        
        return 'No se pudo extraer mensaje de error';
      });

      console.log('Mensaje de error:');
      console.log('=================================================');
      console.log(errorMessage);
      console.log('=================================================\n');

      // Obtener el HTML del body
      const bodyHTML = await page.content();
      const snippet = bodyHTML.substring(0, 2000);
      
      console.log('HTML (primeros 2000 caracteres):');
      console.log('=================================================');
      console.log(snippet);
      console.log('=================================================\n');

    } else if (status === 200) {
      console.log('✓ PÁGINA CARGADA CORRECTAMENTE (200 OK)\n');
      await page.screenshot({ path: 'page-screenshot.png' });
      console.log('✓ Screenshot guardado en: page-screenshot.png\n');
    } else {
      console.log(`→ Código de respuesta inesperado: ${status}\n`);
    }

  } catch (error) {
    console.error('\n✗ ERROR:', error.message);
    await page.screenshot({ path: 'error-crash-screenshot.png' });
    console.log('✓ Screenshot guardado en: error-crash-screenshot.png\n');
  } finally {
    await browser.close();
    console.log('=================================================\n');
  }
})();
