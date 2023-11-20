class LineChart {
    constructor(parentDiv, legendTitle1, dataArray1, legendTitle2, dataArray2) {
        this.parentDiv = parentDiv;
        this.legendTitle1 = legendTitle1;
        this.dataArray1 = dataArray1;
        this.legendTitle2 = legendTitle2;
        this.dataArray2 = dataArray2;
    }

    render = (Plotly) => {
        const trace1 = {
            ...this.dataArray1,
            name: this.legendTitle1,
            mode: 'lines+markers',
            marker: {
                color: 'orange',
            },
            line: {
                color: 'orange',
            },
        };
        var data = [trace1];
        var maxValue = Math.max(...this.dataArray1.y);

        if(this.dataArray2 !== null) {
            var trace2 = {
                ...this.dataArray2,
                name: this.legendTitle2,
                mode: 'lines+markers',
                marker: {
                    color: '#399be2',
                },
                line: {
                    color: '#399be2',
                }
            };
            data.push(trace2);
            maxValue = Math.max(maxValue, Math.max(...this.dataArray2.y));
        }

        const layout = {
            autosize: true,
            showlegend: true,
            legend: {
                orientation: 'h',
            },
            yaxis: {
                tickmode: "array",
                tickvals: [...Array(maxValue+2).keys()],
            },
            margin: {
                l: 25,
                r: 5,
                b: 20,
                t: 10,
            },
        };
        const config = {
            displayModeBar: false,
            responsive: true
        };

        Plotly.newPlot(this.parentDiv, data, layout, config);
    };
}


export default LineChart;

