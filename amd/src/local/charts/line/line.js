class LineChart {
    constructor(legendTitle1, dataArray1, legendTitle2, dataArray2) {
        this.legendTitle1 = legendTitle1;
        this.dataArray1 = dataArray1;
        this.legendTitle2 = legendTitle2;
        this.dataArray2 = dataArray2;
    }

    render = (Plotly) => {
        var plot = document.createElement("div");
        plot.className = "block_chatbot-plotly-chart";

        const trace1 = {
            x: this.dataArray1.keys(),
            y: this.dataArray1.map(key => this.dataArray1[key]),
            name: this.legendTitle1,
            mode: 'lines+markers'
        };
        var data = [trace1];

        if(this.dataArray2 !== null) {
            var trace2 = {
                x: this.dataArray2.keys(),
                y: this.dataArray2.map(key => this.dataArray2[key]),
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

        Plotly.newPlot(plot, data, layout, config);
        return plot;
    };
}


export default LineChart;

