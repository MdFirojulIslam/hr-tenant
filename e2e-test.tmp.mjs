import puppeteer from "puppeteer-core";
import fs from "fs";

const T = "C:/Users/firoj/AppData/Local/Temp/opencode/";
const token = fs.readFileSync(T + "token.txt", "utf8").trim();
const APP = "http://localhost:8000/";
const ROUTE = "outlet-visit/outlet-visit-report";

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await puppeteer.launch({
    executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe",
    headless: "new",
    args: ["--no-sandbox", "--disable-gpu"],
});
const page = await browser.newPage();
page.setDefaultTimeout(60000);

page.on("pageerror", (e) => console.log("  [PAGE ERROR]", String(e).slice(0, 200)));

await page.evaluateOnNewDocument((tok) => {
    localStorage.setItem("token", tok);
    localStorage.setItem("roleId", "1");
    localStorage.setItem("permissions", JSON.stringify([77]));
    localStorage.setItem(
        "user",
        JSON.stringify({ id: 6, Full_Name: "Saad" }),
    );
    localStorage.setItem("tokenExpiration", String(Date.now() + 3600000));
}, token);

console.log("loading app...");
await page.goto(`${APP}${ROUTE}`, { waitUntil: "networkidle2", timeout: 90000 });
await sleep(3000);
console.log("title:", await page.title());
console.log("url:", page.url());

// confirm report table mounted
await page.waitForSelector(".report-wrapper", { timeout: 30000 });

// ---------- helpers ----------
async function setDate(iso) {
    await page.evaluate((v) => {
        const el = document.querySelector(".report-wrapper input[type=date]");
        const setter = Object.getOwnPropertyDescriptor(
            window.HTMLInputElement.prototype,
            "value",
        ).set;
        setter.call(el, v);
        el.dispatchEvent(new Event("input", { bubbles: true }));
        el.dispatchEvent(new Event("change", { bubbles: true }));
    }, iso);
    await sleep(400);
}

async function pickMultiselect(containerSel, labelText) {
    const ok = await page.evaluate(
        (sel, label) => {
            const c = document.querySelector(sel);
            if (!c) return false;
            const input = c.querySelector(".multiselect__tags");
            if (!input) return false;
            input.dispatchEvent(new MouseEvent("mousedown", { bubbles: true }));
            return true;
        },
        containerSel,
        labelText,
    );
    if (!ok) throw new Error("multiselect not found: " + containerSel);
    await sleep(500);
    // search to filter options
    await page.evaluate((sel, txt) => {
        const c = document.querySelector(sel);
        const s = c.querySelector(".multiselect__input");
        if (!s) return false;
        const setter = Object.getOwnPropertyDescriptor(
            window.HTMLInputElement.prototype,
            "value",
        ).set;
        setter.call(s, txt);
        s.dispatchEvent(new Event("input", { bubbles: true }));
        return true;
    }, containerSel, labelText);
    await sleep(700);
    const clicked = await page.evaluate((sel, label) => {
        const c = document.querySelector(sel);
        if (!c) return false;
        const opts = [...c.querySelectorAll(".multiselect__option")];
        const target = opts.find((o) =>
            o.textContent.trim().toLowerCase().includes(label.toLowerCase()),
        );
        if (!target) return false;
        target.dispatchEvent(new MouseEvent("mousedown", { bubbles: true }));
        target.dispatchEvent(new MouseEvent("mouseup", { bubbles: true }));
        target.click();
        return true;
    }, containerSel, labelText);
    if (!clicked) throw new Error(`option "${labelText}" not found in ${containerSel}`);
    await sleep(600);
}

async function tableSnapshot() {
    return page.evaluate(() => {
        const rows = [
            ...document.querySelectorAll(".custom-payroll-table tbody tr"),
        ].filter((tr) => tr.querySelectorAll("td").length > 5);
        const names = [...new Set(rows.map((r) => r.querySelector("td:nth-child(2)")?.textContent.trim()))];
        return { count: rows.length, names };
    });
}

async function employeeDropdownLabel() {
    return page.evaluate(() => {
        const c = document.querySelector(".multiselect-employee-container");
        const single = c?.querySelector(".multiselect__single");
        return single ? single.textContent.trim() : null;
    });
}

// ---------- SCENARIO RUNNER ----------
async function scenario(title, steps) {
    console.log("\n===============================");
    console.log("SCENARIO:", title);
    console.log("===============================");
    // reset: reload page fresh
    await page.goto(`${APP}${ROUTE}`, { waitUntil: "networkidle2" });
    await page.waitForSelector(".custom-payroll-table tbody tr", { timeout: 30000 });
    await sleep(1500);
    try {
        for (const step of steps) await step();
        const snap = await tableSnapshot();
        const label = await employeeDropdownLabel();
        console.log("RESULT -> table rows:", snap.count);
        console.log("RESULT -> employees shown:", snap.names.join(" | "));
        console.log("RESULT -> employee dropdown shows:", label);
    } catch (e) {
        console.log("SCENARIO ERROR:", String(e).slice(0, 300));
    }
}

const DEPT_SEL = ".report-wrapper .multiselect-department-container";
const EMP_SEL = ".report-wrapper .multiselect-employee-container";

await scenario("T1: previous date Aug-18 + dept Modern Trade + Md Asraful Islam", [
    () => setDate("2026-08-18"),
    () => pickMultiselect(DEPT_SEL, "Modern Trade"),
    async () => {
        await sleep(800); // let employee list load
        await pickMultiselect(EMP_SEL, "Md Asraful Islam");
    },
]);

await scenario("T2: previous date Aug-18 + dept + Sujan Sarker", [
    () => setDate("2026-08-18"),
    () => pickMultiselect(DEPT_SEL, "Modern Trade"),
    async () => {
        await sleep(800);
        await pickMultiselect(EMP_SEL, "Sujan Sarker");
    },
]);

await scenario("T3: previous date Aug-18 + dept only (no employee)", [
    () => setDate("2026-08-18"),
    () => pickMultiselect(DEPT_SEL, "Modern Trade"),
]);

await scenario("T4: previous date Aug-18 + employee only (no dept)", [
    () => setDate("2026-08-18"),
    async () => {
        // no department selected -> employee dropdown empty; skip picking
    },
]);

await scenario("T5: TODAY + dept + Md Asraful Islam (regression)", [
    () => setDate("2026-08-23"),
    () => pickMultiselect(DEPT_SEL, "Modern Trade"),
    async () => {
        await sleep(800);
        await pickMultiselect(EMP_SEL, "Md Asraful Islam");
    },
]);

await browser.close();
console.log("\nE2E DONE");
