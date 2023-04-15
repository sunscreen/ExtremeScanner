var network;
var mousePosition;
var offset = [0, 0];
var div;
var isDown = false;
var isMaximized = false;
var ispopped = false;
var data;
var traffictimmer;
function model_from_node(nodeId) {
    return network.body.nodes[nodeId].options.system_model;
}

function model_to_image_encoded(modelchk) {
    //var modelchk=network.body.nodes[nodeId].options.device_model;
    var encodedimage = "unknowndevice.svg";

    if (modelchk === "SUMMIT X450A-48T") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x450a_48t);
    }

    if (modelchk === "SUMMIT 200-48") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_200_48);
    }

    if (modelchk === "SUMMIT X450E-24P") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x450e_24p);
    }

    if (modelchk === "SUMMIT X450E-48T") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x450e_48t);
    }

    if (modelchk === "SUMMIT X450E-48P") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x450e_48p);
    }

    if (modelchk === "SUMMIT X460-24P") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x460_24p);
    }

    if (modelchk === "SUMMIT X460-48T") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x460_48t);
    }
    if (modelchk === "SUMMIT X460-48X") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x460_48x);
    }
    if (modelchk === "SUMMIT X460G2-48T-10G4") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x460_48t_10ge4);
    }

    if (modelchk === "SUMMIT X460-G2-48P-10G4") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x460_g2_48p_10g4);
    }

    if (modelchk === "SUMMIT X460-G2-24P-10G4") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x460_g2_24p_10GE4);
    }


    if (modelchk === "SUMMIT X480-24X") {
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x480_24x);
    }

    if (modelchk === "SUMMIT X650-24X") {
        /*V*/
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x650_24x);
    }

    if (modelchk === "SUMMIT X670V-48X") {
        /*V*/
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x670v_48x);
    }

    if (modelchk === "SUMMIT X670-48X") {
        /*non V*/
        encodedimage = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(summit_x670_48x);
    }

    console.log(modelchk);

    return encodedimage;
}
var popuptime=false;
function popup_timeout(popup) {
setTimeout(function(){
//isPopped=false;
popuptime=true;
//popup.setAttribute("style","");

}, 3000);

}

function create_popup(params,nodeId) {
        if (ispopped==true) return;
        var encodedimage;
        //var nodeId = objref.getNodeAt(params.pointer.DOM);
        var nodeModel = model_from_node(nodeId)
        var popup = document.getElementById("pop");
        document.getElementById("pop").innerHTML = "";
        document.getElementById("pop").style.display = "block";
        document.getElementById("pop").style.pisition = "absolute";
        document.getElementById("pop").style.left = params.event.layerX + "px";
        document.getElementById("pop").style.top = (params.event.layerY) + "px";


        var screenwidth = window.innerWidth;
        var rect = popup.getBoundingClientRect();
        var chkpos = rect.right;
        if (chkpos > screenwidth) {
            var moveleft = params.event.layerX - rect.width;
            popup.style.left = moveleft + "px";
            console.log("soorry offscreen");
        }
        console.log(params);

        //      document.getElementById("pop").innerHTML ='<div >'+nodeModel+'</div>';
        //        document.getElementById("pop").innerHTML = "<pre>"+JSON.stringify(
        //          params,
        //          null,
        //          4
        //        )+"</pre>";
        var stackmode = false;
        var modelchk = network.body.nodes[nodeId].options.system_model;
        var stackmembers = network.body.nodes[nodeId].options.members;
        var nodeName=network.body.nodes[nodeId].options.name;
        var nodeMac=network.body.nodes[nodeId].options.system_mac;
        var nodeIp = network.body.nodes[nodeId].options.system_ipaddress;
        var memlen;

        if (typeof(network.body.nodes[nodeId].options.members) !== 'undefined') memlen = network.body.nodes[nodeId].options.members.length;

        if (modelchk == "SUMMIT VER2 STACK") {
            stackmode = true;
            modelchk="[STACK]";
            nodeIp= network.body.nodes[nodeId].options.stack_ipaddress;
            //console.log(stackmembers[1]);
        } else {
            encodedimage = model_to_image_encoded(modelchk);
        }

        var titlebar = document.createElement("div");
        var titlebar_caption = document.createElement("span");
        titlebar_caption.setAttribute("class","prevent-select");

        titlebar_caption.innerHTML = nodeName+" "+nodeIp;

        titlebar.setAttribute("id", "titlebar");
        titlebar.setAttribute("class", "titlebar w3small");

//        var btnmax = document.createElement("i");
//        btnmax.setAttribute("id", "btnmax");
//        btnmax.setAttribute("class", "w3-tiny fa fa-max-thin button-title");

        //btnmax.innerHTML="&#11036;";

        var btnclose = document.createElement("i");
        btnclose.setAttribute("id", "btnclose");
        btnclose.setAttribute("class", "w3-medium fa fa-times-thin button-title");

        //btnclose.innerHTML="&#10005";
        titlebar.appendChild(titlebar_caption);
        titlebar.appendChild(btnclose);
//        titlebar.appendChild(btnmax);
        //titlebar.innerHTML=nodeModel;
        document.getElementById("pop").appendChild(titlebar);

        if (stackmode == true) {
            for (let i = 0; i < memlen; i++) {
                console.log("building stack img: "+i+" " + stackmembers[i].device_model);
                encodedimage = model_to_image_encoded(stackmembers[i].device_model);

                var elem = document.createElement("img");

                elem.setAttribute("src", encodedimage);
                elem.setAttribute("height", "130");
                elem.setAttribute("width", "90%");
                elem.setAttribute("text-align", "center");

                elem.setAttribute("alt", nodeModel);
                document.getElementById("pop").appendChild(elem);

            }
            var newdiv=document.createElement("div");
            var newh2=document.createElement("h2");
            newh2.innerHTML=nodeMac;
            newdiv.appendChild(newh2);
            popup.appendChild(newdiv);
        } else {

            var elem = document.createElement("img");
            elem.setAttribute("src", encodedimage);
            elem.setAttribute("height", "130");
            elem.setAttribute("width", "90%");
            elem.setAttribute("text-align", "center");
            elem.setAttribute("alt", nodeModel);
            document.getElementById("pop").appendChild(elem);
        }
        titlebar.addEventListener('dblclick', function(event) {
            if (isMaximized == false) {
            event.preventDefault();
            popup.setAttribute("style", "width:100%;position:absolute;display:block;left:0px;top:0px;height:100%;text-align:center;");
            isMaximized = true;

            }

        });
        btnclose.addEventListener('click', function() {
            popup.setAttribute("style", "");
            isMaximized = false;
            ispopped=false;
        });

        ispopped = true;
//        console.log(
//            "click event, getNodeAt returns: " +
//            this.getNodeAt(params.pointer.DOM)
//        );


        document.addEventListener('mousedown', function(e) {

            if (isMaximized === true) return;
            isDown = true;
            offset = [
                popup.offsetLeft - e.clientX,
                popup.offsetTop - e.clientY
            ];
        }, true);

        document.addEventListener('mouseup', function() {
            //      if (ispopped===true) { isDown=false;isMaximized=false; popup.setAttribute("style",""); is_popped=false; return;}

            isDown = false;
        }, true);

        document.addEventListener('mousemove', function(event) {
            event.preventDefault();
            if (isDown) {
                mousePosition = {

                    x: event.clientX,
                    y: event.clientY

                };
                popup.style.left = (mousePosition.x + offset[0]) + 'px';
                popup.style.top = (mousePosition.y + offset[1]) + 'px';


            }
        }, true);


}
var popuptimerId;
function popup_init() {
var nodeId;
    network.on("hoverNode", function(params) {

    nodeId = this.getNodeAt(params.pointer.DOM);
    console.log(nodeId);
    clearTimeout(popuptimerId);
    popuptimerId=setTimeout(function(){ create_popup(params,nodeId); }, 3000);
    });

    network.on("blurNode", function(params) {
        clearTimeout(popuptimerId);
        //params.event = "[original event]";
        //document.getElementById("pop").style.display="none";
        console.log("click event, getNodeAt returns: " + this.getNodeAt(params.pointer.DOM));
    });
    network.on("dragging", function (params) {
        params.event = "[original event]";
        console.log(params);
        clearInterval(traffictimmer);
    });
    network.on("dragEnd", function (params) {
        params.event = "[original event]";
        console.log(params);
        traffictimmer=setInterval(animate,1000);

    });

    network.on("zoom", function (params) {
        params.event = "[original event]";
        console.log(params);
        clearInterval(traffictimmer);

    });

}

function get_node_key(item,nodedata) {
    for (const key in nodedata) {
        const value = nodedata[key]["name"];
        console.log(key, value);
        if (value === item) { return key; }
    }
return -1;
}
var edges;
var edgesarray=[];

function createmap(m_jsondata) {

    nodedata = m_jsondata["nodes"];
    edges=new vis.DataSet(m_jsondata["links"]);
    for (const key in nodedata) {
        const value = nodedata[key];
        console.log(key, value);

    }

    // create a network
    var container = document.getElementById("mynetwork");

    data = {
        //        nodes: m_jsondata["nodes"],
        nodes: nodedata,
        edges: edges
    };

    var options = {
        layout: {
        randomSeed: 6
        },
        autoResize: true,
        height: '100%',
        width: '100%',
        interaction: {
            hover: true
        },

        edges: {
         smooth: {
         "forceDirection": "vertical",
         type: "straightCross",
         roundness: 0.66
        }
        },
        nodes: {
        },
        physics: {

//            forceAtlas2Based: {
//                gravitationalConstant: -50,
//                centralGravity: 0.003,
//                springLength: 140,
//                springConstant: 0.22,
//            },
            repulsion: {
                nodeDistance: 600, // Put more distance between the nodes.
                centralGravity:0.120,
                springLength:0.7,

                springConstant:0.0009,
            },
            maxVelocity: 0,
            //            solver: "forceAtlas2Based",
            solver: "repulsion",

            timestep: 0.35,
            //stabilization: { iterations: 150 },
        },

    };

    network = new vis.Network(container, data, options);
//    for (const key in m_jsondata["links"]) {
//        const value = m_jsondata["links"][key]["to"];
//        console.log(key, value);
//      edgesarray.push({edge:value,trafficSize:2});
//    }
    //var sov1key = get_edges_key("sov-sw1.as31595.net",m_jsondata["nodes"]);
//    var st1=m_jsondata["links"][2]["id"];
//    var sov1=m_jsondata["links"][58]["id"];
//    var sov1_out=m_jsondata["links"][59]["id"];

//    edgesarray.push({edge:sov1,trafficSize:5});

//    edgesarray.push({edge:sov1_out,trafficSize:5});

    for (var x=1;x<62;x++) {
    var st1=m_jsondata["links"][x]["id"];
    edgesarray.push({edge:st1,trafficSize:2});
    }

    popup_init();
    traffictimmer=setInterval(animate,1000);
}

    function animate() {
     network.animateTraffic(edgesarray);
    }

function initwindow() {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            var jsondata = JSON.parse(xhr.responseText);
            console.log(jsondata);
            createmap(jsondata);
        }
    };
    xhr.open('GET', 'http://83.167.165.155/netgraph/test.php?action=getmap', true);
    xhr.send();

}
window.onload = initwindow();