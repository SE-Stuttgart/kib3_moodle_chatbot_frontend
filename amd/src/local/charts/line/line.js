class LineChart {
    constructor(targetDiv, dataArray1, dataArray2) {
        this.targetDiv = targetDiv;
        this.dataArray1 = dataArray1;
        this.dataArray2 = dataArray2;
    }

    render = (Plotly) => {
        var trace1 = this.dataArray1;
        trace1.mode = 'lines+markers';
        var data = [trace1];

        if(this.dataArray2 !== null) {
            var trace2 = this.dataArray2;
            trace2.mode = 'lines+markers';
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
        };
        const config = {
            displayModeBar: false,
        };

        Plotly.newPlot(this.targetDiv, data, layout, config);
    };
}


export default LineChart;

