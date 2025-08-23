module.exports = {
  fixturesFolder: false,
  screenshotOnRunFailure: false,
  trashAssetsBeforeRuns: false,
  video: false,
  numTestsKeptInMemory: 0,
  retries: {
    runMode: 2,
    openMode: 0
  },
  e2e: {
    setupNodeEvents (on, config) {},
    experimentalRunAllSpecs: true
  },
  component: {
    setupNodeEvents (on, config) {},
    specPattern: '**/*.cy.{js,jsx,ts,tsx}'
  }
}
