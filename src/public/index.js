async function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
          center: {lat: 32.6040386, lng: -115.4804487},
          zoom: 20
        });
        let markers = await getMarkers();
        let lengthMarker = Object.keys(markers).length - 1;

        const customSvgMarker = {
            path: "M 187.144 216.366 l 4.622 -32.482 l 19.482 -12.094 c 3.263 -2.024 5.02 -5.786 4.477 -9.587 l -7.478 -52.448 c -3.053 -20.09 -20.811 -36.466 -39.77 -36.839 c 10.833 -7.076 18.019 -19.339 18.019 -33.264 C 186.496 17.788 168.795 0 147.037 0 s -39.458 17.788 -39.458 39.652 c 0 13.925 7.186 26.187 18.019 33.264 c -18.959 0.375 -36.718 16.751 -39.781 36.927 l -7.466 52.36 c -0.543 3.801 1.214 7.563 4.476 9.587 l 19.48 12.094 l 4.683 32.9 c -35.192 5.326 -63.97 17.865 -63.97 38.605 C 43.019 283.978 97.697 297 148.5 297 s 105.48 -13.022 105.48 -41.611 C 253.981 234.09 223.627 221.438 187.144 216.366 Z M 147.037 19.354 c 11.086 0 20.104 9.105 20.104 20.298 c 0 11.192 -9.019 20.299 -20.104 20.299 c -11.085 0 -20.104 -9.106 -20.104 -20.299 C 126.934 28.46 135.952 19.354 147.037 19.354 Z M 98.402 158.679 l 6.561 -46.018 c 1.681 -11.063 11.435 -20.408 21.298 -20.408 h 41.555 c 9.862 0 19.615 9.346 21.283 20.32 l 6.573 46.105 l -17.953 11.145 c -2.435 1.512 -4.074 4.023 -4.478 6.858 l -10.3 72.378 c -0.005 0.037 -0.011 0.075 -0.016 0.112 c -0.243 1.863 -1.899 3.226 -2.67 3.226 h -26.437 c -0.771 0 -2.428 -1.362 -2.671 -3.226 c -0.005 -0.037 -0.011 -0.075 -0.016 -0.112 l -10.302 -72.378 c -0.403 -2.836 -2.043 -5.347 -4.477 -6.858 L 98.402 158.679 Z M 213.183 268.789 c -17.133 5.712 -40.106 8.857 -64.683 8.857 c -24.577 0 -47.549 -3.146 -64.683 -8.857 c -16.203 -5.4 -21.443 -11.36 -21.443 -13.4 c 0 -2.04 5.24 -8 21.443 -13.4 c 7.604 -2.535 16.365 -4.559 25.901 -6.029 l 2.246 15.779 c 1.496 11.227 11.084 20.014 21.854 20.014 h 26.437 c 10.77 0 20.357 -8.785 21.854 -20.013 l 2.305 -16.2 c 10.648 1.496 20.41 3.663 28.769 6.449 c 16.203 5.4 21.443 11.36 21.443 13.4 C 234.626 257.429 229.386 263.389 213.183 268.789 Z",
            fillColor: "black",
            fillOpacity: 1,
            strokeWeight: 0,
            rotation: 0,
            scale: 0.2,
            anchor: new google.maps.Point(15, 30),
        };
        for (let index in markers) {
            var lat = parseFloat(markers[index].latitude);
            var lng = parseFloat(markers[index].longitude);
            new google.maps.Marker({
                position: {lat:lat, lng: lng}, 
                map: map,
                title: 'Marked date: ' + markers[index].date,
                animation: lengthMarker == index ? google.maps.Animation.BOUNCE : google.maps.Animation.NONE,
                icon: lengthMarker == index ? customSvgMarker : null
            });
        }

}


async function getMarkers() 
{      
    let response = await fetch('http://localhost/mapping')
    let result =  await response.json();
    return result;
}