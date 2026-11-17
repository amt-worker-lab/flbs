#!/usr/bin/env node
"use strict";

// Runs extension startup scenarios in jsdom with browser APIs stubbed offline.
const fs = require("node:fs");
const path = require("node:path");
const assert = require("node:assert/strict");
const { JSDOM, ResourceLoader, VirtualConsole } = require(path.join(__dirname, "..", "node_modules", "jsdom"));

const extensionDir = __dirname;
const manifestScripts = [
  "includes/pdfobject.js",
  "includes/Chart.js",
  "includes/jshash-2.2/md5.js",
  "includes/sha1.js",
  "includes/oauth.js",
  "huge_vars.js",
  "global.js",
  "includes/Event.js",
  "includes/Magnifier.js",
  "includes/color-thief.js",
  "test.js",
  "scrapy.js",
  "script.js",
  "script-2.js",
];

class OfflineResourceLoader extends ResourceLoader {
  fetch() {
    return null;
  }
}

function describeException(error) {
  if (!error) return "none";
  const stack = String(error.stack || "");
  const location = stack.match(/(?:^|[ (])((?:[^\s()]+\/)?[^\s()/]+\.js:\d+:\d+)/m);
  const suffix = location ? ` at ${location[1]}` : "";
  return `${error.name || "Error"}: ${error.message || String(error)}${suffix}`;
}

async function runScenario({ name, url, scripts, bodyHTML = "", setupWindow, instrument }) {
  const storageData = { scriptVars: {} };
  const storageListeners = [];
  const report = {};
  const virtualConsole = new VirtualConsole();
  virtualConsole.on("jsdomError", (error) => console.error(`[jsdom] ${error.message}`));

  const dom = new JSDOM("<!doctype html><html><body></body></html>", {
    url,
    runScripts: "outside-only",
    pretendToBeVisual: true,
    resources: new OfflineResourceLoader(),
    virtualConsole,
    beforeParse(window) {
      window.console = {
        log() {},
        info() {},
        warn(...args) { console.warn(...args); },
        error(...args) { console.error(...args); },
      };
      if (!("innerText" in window.HTMLElement.prototype)) {
        Object.defineProperty(window.HTMLElement.prototype, "innerText", {
          configurable: true,
          get() { return this.textContent; },
          set(value) { this.textContent = value; },
        });
      }
      window.localStorage.setItem("mt_captcha", "0");
      window.setInterval = () => 0;
      window.clearInterval = () => {};
      window.setTimeout = () => 0;
      window.clearTimeout = () => {};
      window.fetch = async () => ({
        ok: false,
        status: 0,
        text: async () => "",
        json: async () => ({}),
      });
      window.XMLHttpRequest = class OfflineXMLHttpRequest {
        open() {}
        setRequestHeader() {}
        send() {}
        abort() {}
      };
      window.alert = () => {};
      window.GM_xmlhttpRequest = () => {
        throw new Error("Network requests are disabled in test-runner");
      };
      Object.defineProperty(window.document, "readyState", {
        configurable: true,
        value: "interactive", // manifest uses run_at=document_end
      });
      // A standalone jsdom window is a top-level browsing context, so its
      // built-in window.top and window.self already refer to the same object.

      const emptyEvent = { addListener() {} };
      window.chrome = {
        runtime: {
          onMessage: emptyEvent,
          sendMessage(message, callback) { if (callback) callback({}); },
        },
        storage: {
          local: {
            get(key, callback) {
              queueMicrotask(() => {
                try {
                  callback(key == null ? { ...storageData } : { [key]: storageData[key] });
                } catch (error) {
                  report.startupException = error;
                }
              });
            },
            set(values, callback) {
              Object.assign(storageData, values);
              if (callback) queueMicrotask(() => {
                try { callback(); } catch (error) { report.startupException = error; }
              });
            },
          },
          onChanged: { addListener(fn) { storageListeners.push(fn); } },
        },
        extension: {
          onRequest: emptyEvent,
          onMessage: emptyEvent,
          sendMessage(message, callback) { if (callback) callback({}); },
          sendRequest(message, callback) { if (callback) callback({}); },
        },
        tabs: { query(query, callback) { callback([]); }, sendMessage() {} },
      };
      if (setupWindow) setupWindow(window);
    },
  });
  dom.window.document.body.innerHTML = bodyHTML;

  function loadScript(file) {
    const source = fs.readFileSync(path.join(extensionDir, file), "utf8");
    dom.window.eval(`${source}\n//# sourceURL=${file}`);
  }

  try {
    for (const file of scripts) loadScript(file);
    if (instrument) instrument(dom.window, report);
    loadScript("mturk_load.js");
    // Chrome storage callbacks are asynchronous. Let startup settle before
    // checking call reports or DOM changes.
    await new Promise((resolve) => setImmediate(resolve));
    return { dom, report };
  } catch (error) {
    dom.window.close();
    throw error;
  }
}

const tests = [
  {
    name: "worker home invokes amt_home_show_tasks",
    url: "https://worker.mturk.com/",
    scripts: ["huge_vars.js", "global.js"],
    instrument(window, report) {
      const prototype = window.AMT___core_factory.prototype;
      const original = prototype.amt_home_show_tasks;
      prototype.amt_home_show_tasks = function (...args) {
        report.called = true;
        try {
          report.returnValue = original.apply(this, args);
          return report.returnValue;
        } catch (error) {
          report.exception = error;
          return undefined;
        }
      };
    },
    check({ report }) {
      assert.equal(report.called, true, "amt_home_show_tasks should be called");
      assert.equal(report.exception, undefined, "amt_home_show_tasks should not throw");
      assert.equal(report.returnValue, undefined, "expected return value to be undefined");
    },
    describe({ report }) {
      console.log(`  amt_home_show_tasks called: ${report.called}`);
      console.log(`  Return value: ${String(report.returnValue)}`);
      console.log(`  Exception: ${report.exception ? report.exception.stack || report.exception : "none"}`);
    },
  },
  {
    name: "localhost HIT page runs MT__RUN with an empty body",
    url: "http://localhost/mturk/.dev/HITs/test.html",
    scripts: manifestScripts,
    instrument(window, report) {
      const original = window.MT__RUN;
      window.MT__RUN = function (...args) {
        report.called = true;
        try {
          report.returnValue = original.apply(this, args);
          return report.returnValue;
        } catch (error) {
          report.exception = error;
          return undefined;
        }
      };
    },
    check({ dom, report }) {
      assert.equal(dom.window.top, dom.window.self, "top window should equal self");
      assert.equal(report.called, true, "MT__RUN should be called");
      assert.equal(report.exception, undefined, "MT__RUN should not throw");
      assert.equal(dom.window.document.body.innerHTML, "", "body should remain empty");
    },
    describe({ dom, report }) {
      console.log(`  window.top === window.self: ${dom.window.top === dom.window.self}`);
      console.log(`  MT__RUN called: ${report.called}`);
      console.log(`  Return value: ${String(report.returnValue)}`);
      console.log(`  Exception: ${report.exception ? report.exception.stack || report.exception : "none"}`);
      console.log(`  document.body.innerHTML: ${JSON.stringify(dom.window.document.body.innerHTML)}`);
    },
  },
];

async function main() {
  let failed = 0;
  for (const test of tests) {
    let result;
    try {
      result = await runScenario(test);
      console.log(`Test: ${test.name}`);
      test.describe(result);
      test.check(result);
      console.log("  Result: PASS");
    } catch (error) {
      failed++;
      console.log(`Test: ${test.name}`);
      console.error(`  Result: FAIL\n  ${error.stack || error}`);
    } finally {
      if (result) result.dom.window.close();
    }
  }

  const hitsDirectory = path.resolve(extensionDir, "..", "..", "HITs");
  const htmlFiles = [];
  function collectHtmlFiles(directory) {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
      const fullPath = path.join(directory, entry.name);
      if (entry.isDirectory()) collectHtmlFiles(fullPath);
      else if (entry.isFile()) htmlFiles.push(fullPath);
    }
  }
  collectHtmlFiles(hitsDirectory);
  htmlFiles.sort();

  let fixturesWithExceptions = 0;
  let fixturesWithCalls = 0;
  for (const file of htmlFiles) {
    const relativePath = path.relative(hitsDirectory, file);
    const urlPath = relativePath.split(path.sep).map(encodeURIComponent).join("/");
    const name = `HITs/${relativePath}`;
    let result;
    try {
      result = await runScenario({
        name,
        url: `http://localhost/mturk/.dev/HITs/${urlPath}`,
        bodyHTML: fs.readFileSync(file, "utf8"),
        scripts: manifestScripts,
        instrument(window, report) {
          const original = window.MT__RUN;
          window.MT__RUN = function (...args) {
            report.called = true;
            try {
              report.returnValue = original.apply(this, args);
              return report.returnValue;
            } catch (error) {
              report.exception = error;
              return undefined;
            }
          };
        },
      });
      const { report } = result;
      const exception = report.exception || report.startupException;
      if (report.called) fixturesWithCalls++;
      if (exception) fixturesWithExceptions++;
      console.log(`${exception ? "ERROR" : "OK"} ${name} | MT__RUN called=${Boolean(report.called)} | return=${String(report.returnValue)} | exception=${describeException(exception)}`);
    } catch (error) {
      fixturesWithExceptions++;
      console.log(`ERROR ${name} | MT__RUN called=false | return=unavailable | exception=${describeException(error)}`);
    } finally {
      if (result) result.dom.window.close();
    }
  }

  console.log(`\nFixed tests: ${tests.length - failed}/${tests.length} passed`);
  console.log(`HIT HTML fixtures: ${htmlFiles.length}`);
  console.log(`Fixtures that called MT__RUN: ${fixturesWithCalls}/${htmlFiles.length}`);
  console.log(`Fixtures with exceptions: ${fixturesWithExceptions}/${htmlFiles.length}`);
  if (failed || fixturesWithExceptions) process.exitCode = 1;
}
main().catch((error) => {
  console.error(error.stack || error);
  process.exitCode = 1;
});
