// Color variables based on css variable.
// ----------------------------------------------
const _body = getComputedStyle(document.body);
let primaryColor, textColor, secondaryColor;



// Chart and data variable
// ----------------------------------------------
let networkChart;


const hddData = [
    { period: "Nov-04", hddSpace: 57 },
    { period: "Nov-05", hddSpace: 69 },
    { period: "Nov-06", hddSpace: 70 },
    { period: "Nov-07", hddSpace: 62 },
    { period: "Nov-08", hddSpace: 73 },
    { period: "Nov-09", hddSpace: 79 },
    { period: "Nov-10", hddSpace: 76 },
    { period: "Nov-11", hddSpace: 77 },
    { period: "Nov-12", hddSpace: 73 },
    { period: "Nov-13", hddSpace: 52 },
    { period: "Nov-14", hddSpace: 57 },
    { period: "Nov-15", hddSpace: 50 },
    { period: "Nov-16", hddSpace: 60 },
    { period: "Nov-17", hddSpace: 55 },
    { period: "Nov-18", hddSpace: 70 },
    { period: "Nov-19", hddSpace: 68 },
    { period: "Nov-20", hddSpace: 57 },
    { period: "Nov-21", hddSpace: 62 },
    { period: "Nov-22", hddSpace: 53 },
    { period: "Nov-23", hddSpace: 69 },
    { period: "Nov-24", hddSpace: 59 },
    { period: "Nov-25", hddSpace: 67 },
    { period: "Nov-26", hddSpace: 69 },
    { period: "Nov-27", hddSpace: 59 },
    { period: "Nov-28", hddSpace: 67 },
    { period: "Nov-29", hddSpace: 69 },
    { period: "Nov-30", hddSpace: 58 },
    { period: "Des-01", hddSpace: 50 },
    { period: "Des-02", hddSpace: 47 },
    { period: "Des-03", hddSpace: 65 }
]

const earningData = [
    { period: "Nov-04", earning: 945 },
    { period: "Nov-05", earning: 754 },
    { period: "Nov-06", earning: 805 },
    { period: "Nov-07", earning: 855 },
    { period: "Nov-08", earning: 678 },
    { period: "Nov-09", earning: 987 },
    { period: "Nov-10", earning: 1026 },
    { period: "Nov-11", earning: 855 },
    { period: "Nov-12", earning: 730 },
    { period: "Nov-13", earning: 920 },
    { period: "Nov-14", earning: 870 },
    { period: "Nov-15", earning: 900 },
    { period: "Nov-16", earning: 890 },
    { period: "Nov-17", earning: 750 },
    { period: "Nov-18", earning: 900 },
    { period: "Nov-19", earning: 880 },
    { period: "Nov-20", earning: 870 },
    { period: "Nov-21", earning: 820 },
    { period: "Nov-22", earning: 930 },
    { period: "Nov-23", earning: 945 },
    { period: "Nov-24", earning: 754 },
    { period: "Nov-25", earning: 805 },
    { period: "Nov-26", earning: 755 },
    { period: "Nov-27", earning: 678 },
    { period: "Nov-28", earning: 987 },
    { period: "Nov-29", earning: 1026 },
    { period: "Nov-30", earning: 885 },
    { period: "Des-01", earning: 878 },
    { period: "Des-02", earning: 922 },
    { period: "Des-03", earning: 875 },
]



// Initialize the chart when the document is ready
// ----------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    updateColorVars();
    // HDD  chart - Area chart
    const hddChart = new Chart(
        document.getElementById("_dm-hddChart"), {
        type: "line",
        data: {
            datasets: [
                {
                    label: "Usage",
                    data: hddData,
                    borderColor: "white",
                    backgroundColor: "rgba(255,255,255,.4)",
                    fill: "start",
                    parsing: {
                        xAxisKey: "period",
                        yAxisKey: "hddSpace"
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 250,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    caretSize: 0,
                    yAlign: "center",
                    callbacks: {
                        label: (context) => {
                            let label = context.dataset.label || "";
                            if (context.parsed.y !== null) label += " : " + context.parsed.y + "%";
                            return label;
                        }
                    }
                }
            },

            interaction: {
                mode: "index",
                intersect: false,
            },

            scales: {
                y: {
                    display: false
                },
                x: {
                    display: false
                }
            },
            radius: 1,
            elements: {
                line: {
                    tension: 0.15
                }
            }
        }
    }
    )


    // Earning chart - Line chart
    const earningChart = new Chart(
        document.getElementById("_dm-earningChart"), {
        type: "line",
        data: {
            datasets: [
                {
                    label: "Earning",
                    data: earningData,
                    borderWidth: 2,
                    borderColor: "white",
                    parsing: {
                        xAxisKey: "period",
                        yAxisKey: "earning"
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 250,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    caretSize: 0,
                    yAlign: "center",
                    callbacks: {
                        label: (context) => {
                            let label = context.dataset.label || "";
                            if (context.parsed.y !== null) label += " : $" + context.parsed.y + ".00";
                            return label;
                        }
                    }
                }
            },

            interaction: {
                mode: "index",
                intersect: false,
            },

            scales: {
                y: {
                    display: false
                },
                x: {
                    display: false
                }
            },
            radius: .5,
            elements: {
                line: {
                    tension: 0.5
                }
            }
        }
    }
    )



    // Sales Chart - Bar chart
    const salesChart = new Chart(document.getElementById("_dm-salesChart"), {
        type: "bar",
        data: {
            datasets: [
                {
                    data: earningData,
                    drawBorder: false,
                    borderRadius: 3,
                    backgroundColor: "rgba(255,255,255, .6)",
                    parsing: {
                        xAxisKey: "period",
                        yAxisKey: "earning"
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 250,
            layout: {
                padding: 0
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    caretSize: 0,
                    yAlign: "center",
                    callbacks: {
                        label: (context) => {
                            let label = context.dataset.label || "";
                            if (context.parsed.y !== null) label += ` ${context.parsed.y} Sales`;
                            return label;
                        }
                    }
                }
            },

            interaction: {
                mode: "index",
                intersect: false,
            },

            scales: {
                y: {
                    display: false
                },
                x: {
                    display: false
                }
            }
        }
    })



    // Task Chart - Horizontal bar
    const taskChart = new Chart(document.getElementById("_dm-taskChart"), {
        type: "bar",
        data: {
            datasets: [
                {
                    data: [
                        { kind: "Incidential", complete: 45 },
                        { kind: "Coordinated", complete: 54 },
                        { kind: "Planned", complete: 24 },
                        { kind: "Other", complete: 34 }
                    ],
                    barThickness: 7,
                    borderRadius: 3,
                    backgroundColor: "rgba(255,255,255, .7)",
                    parsing: {
                        yAxisKey: "kind",
                        xAxisKey: "complete"
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 250,
            layout: {
                padding: 0
            },
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    caretSize: 0,
                    yAlign: "center",
                    callbacks: {
                        label: (context) => {
                            let label = context.dataset.label || "";
                            if (context.parsed.x !== null) label += " " + context.parsed.x + " Items Completed";
                            return label;
                        }
                    }
                }
            },

            interaction: {
                mode: "index",
                intersect: true,
            },

            scales: {
                y: {
                    display: false
                },
                x: {
                    display: false
                }
            }
        }
    })
});



// Update the chart"s colors when the color scheme changes.
// ----------------------------------------------
const updateDashboardChart = function () {

    // Update all color variables
    updateColorVars();

    // Update network chart
    networkChart.data.datasets[0].borderColor = primaryColor;
    networkChart.data.datasets[0].backgroundColor = primaryColor;
    networkChart.data.datasets[1].borderColor = secondaryColor;
    networkChart.data.datasets[1].backgroundColor = secondaryColor;
    networkChart.options.plugins.legend.labels.color = textColor;
    networkChart.options.scales.x.ticks.color = textColor;
    networkChart.update();

};

["change.nf.colormode", "scheme-changed"].forEach(ev => document.addEventListener(ev, updateDashboardChart))


// Get a secondary color based on the selected primary color.
// ----------------------------------------------
let getSecondaryColor = (rgb) => {
    rgb = rgb.split(",");
    let r = rgb[0] / 255, g = rgb[1] / 255, b = rgb[2] / 255;
    let v = Math.max(r, g, b), c = v - Math.min(r, g, b), f = (1 - Math.abs(v + v - c - 1));
    let h = c && ((v == r) ? (g - b) / c : ((v == g) ? 2 + (b - r) / c : 4 + (r - g) / c));
    let l = getBgLightness();

    return hslToHex(Math.round(60 * (h < 0 ? h + 6 : h)), l > 70 ? Math.round(f ? c / f : 0 * 100) + 30 : 15, l > 70 ? .9 : .25);
}



// Convert hsl color to hex
// ----------------------------------------------
function hslToHex(h, s, l) {
    const a = s * Math.min(l, 1 - l) / 100;
    const f = n => {
        const k = (n + h / 30) % 12;
        const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
        return Math.round(255 * color).toString(16).padStart(2, '0');   // convert to Hex and prefix "0" if needed
    };
    return `#${f(0)}${f(8)}${f(4)}`;
}



// Get a lighting value from the background to determine if it is a dark or light scheme.
// ----------------------------------------------
let getBgLightness = () => {
    let clr = _body.getPropertyValue("--bs-component-bg-rgb").split(",");
    clr[0] /= 255, clr[1] /= 255, clr[2] /= 255;
    let x = Math.max(clr[0], clr[1], clr[2]), n = x - Math.min(clr[0], clr[1], clr[2]);
    return Math.round((x + x - n) / 2 * 100);
}



// Update colors
// ----------------------------------------------
let updateColorVars = () => {
    primaryColor = _body.getPropertyValue("--bs-primary");
    textColor = _body.getPropertyValue("--bs-body-color");
    secondaryColor = getSecondaryColor(_body.getPropertyValue("--bs-primary-rgb"));
    return;
}
