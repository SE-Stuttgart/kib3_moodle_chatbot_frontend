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
            mode: 'lines+markers'
        };
        var data = [trace1];

        if(this.dataArray2 !== null) {
            var trace2 = {
                ...this.dataArray2,
                name: this.legendTitle2,
                mode: 'lines+markers'
            };
            data.push(trace2);
        }

        const layout = {
            autosize: true,
            margin: {
                l: 20,
                r: 2,
                b: 20,
                t: 2,
                pad: 2
            },
            showlegend: true,
        };
        const config = {
            displayModeBar: false,
        };

        Plotly.newPlot(this.parentDiv, data, layout, config);
    };
}


export default LineChart;

