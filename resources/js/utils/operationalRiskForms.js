export function todayIsoDate() {
    return new Date().toISOString().slice(0, 10);
}

export function emptyExceptionForm(lineDate = todayIsoDate()) {
    return {
        line_date: lineDate,
        major_exceptions: '',
        correlated_risks: '',
        risk_family: '',
        gravity: null,
        probability: null,
    };
}
