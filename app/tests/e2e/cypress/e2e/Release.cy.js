describe('Release page', () => {
  beforeEach(() => {
    cy.visit('/releases/2022.01.01', { waitForApi: true })
  })

  it('shows version', () => {
    cy.contains('h1', '2022.01.01')
  })

  it('links download', () => {
    cy.get('[data-test=release-download]').should('have.attr', 'href').and('contain', '2022.01.01')
  })
})
