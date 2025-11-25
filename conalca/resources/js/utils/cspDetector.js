/**
 * Detector de problemas con Content Security Policy
 * Detecta específicamente problemas con eval() y 'unsafe-eval'
 */

(function() {
    'use strict';

    // Test si eval() está bloqueado por CSP
    function testEvalBlocked() {
        try {
            eval('1');
            console.log('✅ eval() permitido - CSP no bloquea eval');
            return false;
        } catch (e) {
            if (e instanceof EvalError || (e.message && e.message.includes('eval'))) {
                console.error('❌ eval() bloqueado por CSP');
                if (window.errorHandler) {
                    window.errorHandler.reportError(
                        'Content Security Policy bloqueando eval()',
                        {
                            type: 'CSP Violation - eval()',
                            message: 'El Content Security Policy está bloqueando el uso de eval(). Se requiere "unsafe-eval" en script-src.',
                            solution: 'Agregar "unsafe-eval" al CSP o evitar uso de eval() en el código.',
                            error: e.message
                        }
                    );
                }
                return true;
            }
        }
        return false;
    }

    // Test si new Function() está bloqueado
    function testFunctionBlocked() {
        try {
            new Function('return 1')();
            console.log('✅ new Function() permitido');
            return false;
        } catch (e) {
            console.error('❌ new Function() bloqueado por CSP');
            if (window.errorHandler) {
                window.errorHandler.reportError(
                    'Content Security Policy bloqueando new Function()',
                    {
                        type: 'CSP Violation - new Function()',
                        message: 'El CSP está bloqueando new Function(). Se requiere "unsafe-eval".',
                        error: e.message
                    }
                );
            }
            return true;
        }
    }

    // Capturar violaciones de CSP
    document.addEventListener('securitypolicyviolation', (e) => {
        console.error('🚨 CSP Violation:', e);
        
        const violation = {
            blockedURI: e.blockedURI,
            violatedDirective: e.violatedDirective,
            effectiveDirective: e.effectiveDirective,
            originalPolicy: e.originalPolicy,
            sourceFile: e.sourceFile,
            lineNumber: e.lineNumber,
            columnNumber: e.columnNumber,
            statusCode: e.statusCode
        };

        if (window.errorHandler) {
            window.errorHandler.reportError(
                `CSP Violation: ${e.violatedDirective}`,
                {
                    type: 'Content Security Policy Violation',
                    message: `Bloqueado: ${e.blockedURI || 'inline'}`,
                    directive: e.violatedDirective,
                    details: violation,
                    solution: getCspSolution(e.violatedDirective, e.blockedURI)
                }
            );
        }
    });

    // Sugerir soluciones basadas en el tipo de violación
    function getCspSolution(directive, blockedURI) {
        const solutions = {
            'script-src': blockedURI.includes('eval') 
                ? 'Agregar "unsafe-eval" a script-src o evitar eval()/new Function()'
                : `Agregar '${blockedURI}' a script-src o usar 'self'`,
            'style-src': 'Agregar "unsafe-inline" a style-src para estilos inline',
            'img-src': `Agregar '${blockedURI}' a img-src`,
            'font-src': `Agregar '${blockedURI}' a font-src`,
            'connect-src': `Agregar '${blockedURI}' a connect-src para peticiones AJAX`
        };

        return solutions[directive] || `Revisar directiva ${directive} en CSP`;
    }

    // Ejecutar tests cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                console.group('🔒 CSP Diagnostics');
                const evalBlocked = testEvalBlocked();
                const functionBlocked = testFunctionBlocked();
                
                if (evalBlocked || functionBlocked) {
                    console.warn('⚠️ CSP está bloqueando funciones críticas');
                    console.log('Solución: Habilitar unsafe-eval en ContentSecurityPolicy.php');
                } else {
                    console.log('✅ CSP configurado correctamente');
                }
                console.groupEnd();
            }, 1000);
        });
    }

    // Monitorear errores relacionados con scripts
    const originalError = window.onerror;
    window.onerror = function(message, source, lineno, colno, error) {
        if (message && (
            message.includes('Content Security Policy') ||
            message.includes('CSP') ||
            message.includes('eval') ||
            message.includes('unsafe-eval')
        )) {
            console.error('🚨 Error relacionado con CSP detectado:', message);
        }
        
        if (originalError) {
            return originalError(message, source, lineno, colno, error);
        }
        return false;
    };

    console.log('🔍 CSP Detector inicializado');
})();
