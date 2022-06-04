describe('Download page', () => {
  beforeEach(() => {
    cy.visit('/download')
  })

  it('shows the current release', () => {
    cy.get('[data-test=current-release] a').should('be.visible').contains(/[0-9]{4}\.[0-9]{2}\.[0-9]{2}/)
    cy.get('[data-test=current-release] a').should('have.attr', 'href')
  })

  it('shows the download button', () => {
    cy.get('[data-test=download-release]').should('be.visible').contains(/[0-9]{4}\.[0-9]{2}\.[0-9]{2}/)
    cy.get('[data-test=download-release]').should('have.attr', 'href').and('match', /\.iso$/)
  })

  it('shows mirror list', () => {
    cy.get('[data-test=mirror-list]').should('be.visible')
    cy.get('[data-test=mirror-list] li').should('have.length', 10)
    cy.get('[data-test=mirror-list] li a').should('have.attr', 'href').and('match', /\.iso$/)
  })
})
