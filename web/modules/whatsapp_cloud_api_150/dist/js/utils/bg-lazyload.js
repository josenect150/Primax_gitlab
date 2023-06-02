const sections = document.querySelectorAll('.bg-lazyload');
let sectionsLoaded = 0;

const lazyObserver = new IntersectionObserver((entries, observe) => {
    entries.forEach((entry) => {
        const { isIntersecting, target } = entry;
        if (isIntersecting) {
            if (!target.classList.contains('bg-lazyloaded')) sectionsLoaded += 1
            target.classList.add('bg-lazyloaded')
            if (target.getAttribute('data-bg-lazyload')) target.style.backgroundImage = `url('${target.getAttribute('data-bg-lazyload')}')`
        }

        if (sectionsLoaded == sections.length) observe.disconnect()
    })
})

if (sections.length > 0) sections.forEach(item => lazyObserver.observe(item))