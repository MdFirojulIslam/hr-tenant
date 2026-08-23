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

page.on("pageerror", (e) => {
    const s = String(e?.stack || e);
    console.log(">>> PAGE ERROR:", s.split("\n").slice(0, 3).join(" | "));
});
page.on("console", (m) => {
    const t = m.text();
    if (t.includes("[Vue warn]")) console.log(">>> VUE WARN:", t.slice(0, 250));
});

await page.evaluateOnNewDocument((tok) => {
    localStorage.setItem("token", tok);
    localStorage.setItem("roleId", "1");
    localStorage.setItem("permissions", JSON.stringify([77]));
    localStorage.setItem("user", JSON.stringify({ id: 6, Full_Name: "Saad" }));
    localStorage.setItem("tokenExpiration", String(Date.now() + 3600000));
}, token);

await page.goto(`${APP}${ROUTE}`, { waitUntil: "networkidle2", timeout: 90000 });
await page.waitForSelector(".custom-payroll-table tbody tr", { timeout: 30000 });
await sleep(1500);

async function rowCount(label) {
    const r = await page.evaluate(() => {
        const tb = document.querySelector(".custom-payroll-table tbody");
        return [...tb.rows].filter((x) => x.cells.length > 5).length;
    });
    console.log(`   [rows] ${label}: ${r}`);
}

async function setDate(iso) {
    await page.evaluate((v) => {
        const el = document.querySelector(".report-wrapper input[type=date]");
        const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, "value").set;
        setter.call(el, v);
        el.dispatchEvent(new Event("input", { bubbles: true }));
        el.dispatchEvent(new Event("change", { bubbles: true }));
    }, iso);
    await sleep(500);
}
async function pick(containerSel, labelText) {
    const h = await page.$(`${containerSel} .multiselect__tags`);
    await h.click();
    await sleep(700);
    const ok = await page.evaluate((sel, label) => {
        const c = document.querySelector(sel);
        const opts = [...c.querySelectorAll(".multiselect__option")];
        const t = opts.find((o) => o.textContent.trim().toLowerCase().includes(label.toLowerCase()));
        if (!t) return false;
        t.click();
        return true;
    }, containerSel, labelText);
    if (!ok) throw new Error("option not found: " + labelText);
    await sleep(800);
}
async function setStatus(v) {
    await page.select(".report-wrapper select.form-select", v);
    await sleep(600);
}

console.log("\n== STEP A: change date only (Aug18 -> Aug19 -> Aug18)");
await setDate("2026-08-18"); await rowCount("Aug18");
await setDate("2026-08-19"); await rowCount("Aug19");
await setDate("2026-08-18"); await rowCount("Aug18 again");

console.log("\n== STEP B: status filter only");
await setStatus("Visited"); await rowCount("status=Visited");
await setStatus(""); await rowCount("status=all");

console.log("\n== STEP C: department multiselect");
await pick(".report-wrapper .multiselect-department-container", "Modern Trade");
await rowCount("after dept MT");

console.log("\n== STEP D: employee multiselect");
await pick(".report-wrapper .multiselect-employee-container", "Md Asraful Islam");
await rowCount("after emp Asraful");

console.log("\nDONE");
await browser.close();
