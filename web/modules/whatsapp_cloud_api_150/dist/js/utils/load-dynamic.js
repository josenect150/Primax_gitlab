/**
 * 
 * @param {string} url - Relative script URL to load
 * @param {boolean} defer - Load defer Script
 * @param {boolean} async - Load async Script
 * @returns {Promise}
 */
window.loadDynamic = function (url, defer = false, async = true) {
    return new Promise(function (resolve, reject) {
        try {
            let nwScript = document.createElement('script');

            nwScript.type = 'text/javascript';
            nwScript.defer = defer;
            nwScript.async = async;
            nwScript.src = url;

            nwScript.addEventListener('load', () => {
                resolve({ loaded: true, error: false });
            })

            nwScript.addEventListener('error', () => {
                resolve({ loaded: false, error: true, message: 'Fallo la carga del script: ' + url });
            })

            document.head.insertAdjacentElement('beforeend', nwScript);
        } catch (error) { reject(error); }
    })
}

/**
 * 
 * @param {string} gsapFolder - relative location folder library gsap
 * @param {Array<string>} gsapPlugins - gsap plugins file list
 * @returns 
 */
window.loadGsap = async function (gsapFolder, gsapPlugins) {
    const loadPlugins = (plugins, pluginCallback) => plugins.map(plugin => pluginCallback(plugin))

    try {
        let scripts = gsapPlugins ?
            await Promise.all([loadDynamic(`${gsapFolder}/gsap.min.js`, true),
            ...loadPlugins(gsapPlugins, (plugin) => loadDynamic(`${gsapFolder}/${plugin}`, true))]) :
            await loadDynamic(`${gsapFolder}/gsap.min.js`, true)

        if (Array.isArray(scripts) && scripts.every(item => item.loaded) || scripts.loaded) {
            let mainMessage = 'Se han cargado correctamente gsap';
            message = Array.isArray(scripts) ? `${mainMessage} y sus plugins` : mainMessage
            return { message }
        }
        else {
            let script = Array.isArray(scripts) ? scripts.find(item => !item.loaded) : scripts
            throw `${script.message}`
        }

    } catch (error) {
        throw new Error(error);
    }
}