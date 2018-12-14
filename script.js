window.onload=function () {
    setTimeout(function() {window.location.reload()}, 10 * 60 * 1000)
    startScroll()
    startClock()
    startImage()
}

function setProgress(time) {
    // set correct animation speed for progress bar and reset it to zero
    let elm = document.getElementById('progress')
    if (elm) {
        elm.style.transition = "none"
        elm.style.width = "0"
        // trick to force borwser to update element style
        setTimeout(zero, 100)
    }
    function zero() {
        elm.style.transition = "width " + time + "s linear"
        elm.style.width = "100%"
    }
}

function startImage() {
    // manage image loop
    let elm = document.getElementById('images')
    if (elm) {
        setProgress(5 * 60)
        setTimeout(toggleImage, 5 * 60 * 1000)
    }
}

function toggleImage() {
    // show/hide image
    let elm = document.getElementById('images')
    let loopID, imageTime
    if (elm) {
        num = elm.childElementCount
        imageTime = num < 5 ? 1 : 5 / num
        if (window.getComputedStyle(elm).getPropertyValue('display') == 'none') {
            // show slideshow
            elm.style.display = 'initial'

            // get number of images to determine time for each one
            // total show time is 10 minutes timetable will always get min 5 minutes
            // every image will get max 1 minute of display time
            
            loopImage()
            loopID = setInterval(loopImage, imageTime * 60 * 1000)
        } else {
            // hide slideshow
            elm.style.display = 'none'
            if (num < 5) {
                setProgress((5 - num) * 60)
                setTimeout(toggleImage, (5 - num) * 60 * 1000)
            } else {
                setProgress(5 * 60)
                setTimeout(toggleImage, 5 * 60 * 1000)
            }
        }
    }

    function loopImage() {
        setProgress(imageTime * 60)
        if (!nextImage()) {
            clearInterval(loopID)
            toggleImage()
        }
    }
}

function nextImage() {
    // display next image
    let elm = document.getElementById('images')
    if (elm) {
        if (window.getComputedStyle(elm).getPropertyValue('display') != 'none') {
            // get node list and find first displayed image
            // if none is found show first
            // if last is found hide image and return false
            active = null
            for (let e of elm.children) {
                if (window.getComputedStyle(e).getPropertyValue('display') != 'none') {
                    if (active != null)
                        active.style.display = 'null'
                    active = e
                }
            }
            if (active == null)
                elm.firstElementChild.style.display = 'initial'
            else if (active.nextElementSibling == null) {
                active.style.display = 'none'
                return false
            } else {
                active.style.display = 'none'
                active.nextElementSibling.style.display = 'initial'
            }
        }
        return true
    }
}

function startClock() {
    let elm = document.getElementById("time")
    if (!elm) return
    
    setInterval(loopClock, 1000)
    function loopClock() {
        let time = new Date()
        elm.innerHTML = 
            ("0" + time.getHours()).slice(-2) + 
            "<span" + (time.getSeconds()%2?' class="light"':"") + ">:</span>" +
            ("0" + time.getMinutes()).slice(-2)
    }
}

function startScroll() {
    let tab1 = document.getElementById("tab1")
    if (!tab1) return;
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
        top -= 2;
        if (tab2) {
            tab2.style.top = (top + tab1Height) + "px"
        }
        tab1.style.top = top + "px"
        if (top*-1 + 162 > tab1Height)
            top = 162
    }
}
