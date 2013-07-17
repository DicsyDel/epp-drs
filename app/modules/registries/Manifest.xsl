<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:str="http://exslt.org/strings"
	version="1.0">
	
    <xsl:template match="contact_groups/group">
        <xsl:copy>
        	<xsl:apply-templates select="@*|node()"/>
            <xsl:element name="targets">
	  			<xsl:choose>
	  				<xsl:when test="@apply-to-section">
	  					<xsl:element name="target">        					
		  					<xsl:attribute name="tlds">
		  						<xsl:value-of select="ancestor::section/@tlds" />
		  					</xsl:attribute>
	  					</xsl:element>
	  				</xsl:when>
	  				<xsl:when test="@target-group">
	        			<xsl:variable name="targetGroup" select="@target-group"></xsl:variable>  					
	  					<xsl:copy-of select="../../contact_target_groups/target_group[@name=$targetGroup]/*"/>
	  				</xsl:when>
	  				<xsl:otherwise>
						<xsl:apply-templates select="str:split(ancestor::section/@tlds,',')"/>
					</xsl:otherwise>
				</xsl:choose>
	        </xsl:element>
        </xsl:copy>
    </xsl:template>
 
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template> 
      
    <xsl:template match="//section/config/contacts/contact">
        <xsl:variable name="groupname" select="@group" />
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
            <xsl:copy-of select="../../contact_groups/group[@name = $groupname]/*"/>
            <xsl:element name="targets">
	  			<xsl:choose>
	  				<xsl:when test="../../contact_groups/group[@name=$groupname]/@apply-to-section">
	  					<xsl:element name="target">        					
		  					<xsl:attribute name="tlds">
		  						<xsl:value-of select="ancestor::section/@tlds" />
		  					</xsl:attribute>
	  					</xsl:element>
	  				</xsl:when>
	  				<xsl:when test="../../contact_groups/group[@name=$groupname]/@target-group">
	        			<xsl:variable name="targetGroup" select="../../contact_groups/group[@name=$groupname]/@target-group"></xsl:variable>  					
	  					<xsl:copy-of select="../../contact_target_groups/target_group[@name=$targetGroup]/*"/>
	  				</xsl:when>
	  				<xsl:otherwise>
						<xsl:apply-templates select="str:split(ancestor::section/@tlds,',')"/>
					</xsl:otherwise>
				</xsl:choose>
	        </xsl:element>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="token">
    	<xsl:element name="target">
    		<xsl:attribute name="tlds">
    			<xsl:value-of select='.'/>
    		</xsl:attribute>
    	</xsl:element>
	</xsl:template>
     
    <xsl:template match="//section/config/domain">
        <xsl:variable name="groupname" select="@group" />
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
            <xsl:copy-of select="../contacts"/>
        </xsl:copy>
    </xsl:template>

</xsl:stylesheet>