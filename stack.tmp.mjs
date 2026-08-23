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

const errors = [];
page.on("pageerror", (e) => {
    errors.push(String(e?.stack || e).slice(0, 3000));
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
        const t = opts.find((o) => o.textContent.trim() === label) ||
            opts.find((o) => o.textContent.trim().toLowerCase().includes(label.toLowerCase()));
        if (!t) return false;
        t.click();
        return true;
    }, containerSel, labelText);
    if (!ok) throw new Error("option not found: " + labelText);
    await sleep(800);
}

await setDate("2026-08-18");
await pick(".report-wrapper .multiselect-department-container", "Modern Trade");
console.log("--- after dept pick, errors so far:", errors.length);
await pick(".report-wrapper .multiselect-employee-container", "Md Asraful Islam");
console.log("--- after emp pick, errors:", errors.length);

for (const [i, e] of errors.entries()) {
    console.log(`\n===== ERROR ${i + 1} =====`);
    console.log(e);
}

// dump tbody state
const tbody = await page.evaluate(() => {
    const tb = document.querySelector(".custom-payroll-table tbody");
    return {
        childRows: tb.querySelectorAll("tr").length,
        dataRows: [...tb.rows].filter((r) => r.cells.length > 5).length,
    };
});
console.log("\ntbody state:", JSON.stringify(tbody));

await browser.close();
