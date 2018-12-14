window.onload=function () {
    setTimeout(function() {window.location.reload()}, 10 * 60 * 1000)
    startScroll()
    startClock()
    startImage()
}

function setProgress(time) {
    // set correct animation speed for progress bar and reset it to zero
    let elm = document.getElementById('progress')
    if (!elm) return

    elm.style.transition = "none"
    elm.style.width = "0"
    // trick to force borwser to update element style
    setTimeout(progressRun, 100)

    function progressRun() {
        elm.style.transition = "width " + time + "s linear"
        elm.style.width = "100%"
    }
}

function startImage() {
    // manage image loop
    let elm = document.getElementById('images')
    if (!elm) return

    setProgress(5 * 60)
    setTimeout(toggleImage, 5 * 60 * 1000)
}

function toggleImage() {
    // show/hide image
    let elm = document.getElementById('images')
    let loopID, imageTime
    if (!elm) return

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

    let tab2, time = 0
    
    if (tab1.scrollHeight + 192 > window.innerHeight) {
        // clone main table and paste it under table
        tab2 = tab1.cloneNode(true)
        tab2.setAttribute('id', 'tab2')
        document.body.appendChild(tab2)
        
        // calculate time to scroll
        time = Math.ceil((tab1.scrollHeight + window.innerHeight) / 80); // velocity

        moveDown()
        setInterval(moveDown, (time * 1000) + 50)
    }

    function moveDown() {
        // zero element top
        tab1.style.transition = "none"
        tab1.style.top = "162px"
        
        tab2.style.transition = "none"
        tab2.style.top = (tab1.scrollHeight + 162) + "px"

        setTimeout(scrollRun, 50)

        function scrollRun() {
            tab1.style.transition = "top " + time + "s linear"
            tab1.style.top = "-" + (tab1.scrollHeight - 162) + "px"

            tab2.style.transition = "top " + time + "s linear"
            tab2.style.top = "162px"
        }
    }
}
