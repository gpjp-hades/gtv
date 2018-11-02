window.onload=function () {
    startScroll()
    setTimeout(function() {window.location.reload()}, 10 * 60 * 1000)
}

function startScroll() {
    let tab1 = document.getElementById("tab1")
    let tab1Height = parseInt(window.getComputedStyle(tab1).height)
    let tab2, top
    
    if (tab1Height + 192 > window.innerHeight) {
        tab2 = tab1.cloneNode(true)
        tab2.setAttribute('id', 'tab2')
        document.body.appendChild(tab2)
        top = 162
        setInterval(moveDown, 50)
    }

    function moveDown() {
        top -= 0.5;
        if (tab2) {
            tab2.style.top = (top + tab1Height) + "px"
        }
        tab1.style.top = top + "px"
        if (top*-1 + 162 > tab1Height)
            top = 162
    }
}
